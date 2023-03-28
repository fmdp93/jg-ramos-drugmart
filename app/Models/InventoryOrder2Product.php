<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryOrder2Product extends Model
{
    use HasFactory;

    protected $table = 'inventory_order2_product';

    public $timestamps = false;

    public function insert_products($request, $transaction_id)
    {
        foreach ($request->input('product_id') as $key => $product_id) {
            $InventoryOrder2Product = new InventoryOrder2Product();
            $InventoryOrder2Product->transaction_id = $transaction_id;
            $InventoryOrder2Product->product_id = $product_id;
            $InventoryOrder2Product->quantity = $request->input('quantity')[$key];
            $InventoryOrder2Product->price = $request->input('price')[$key];
            $InventoryOrder2Product->save();
        }
    }

    public static function getOrderedProduct($wheres)
    {
        $query = self::select(DB::raw('
            io2p.transaction_id,
            p.id p_id, p.item_code, p.category_id, p.stock, p.base_price,
            p.markup, p.price, 
            p.name, p.unit, p.description, p.supplier_id, p.expiration_date'))
            ->from('inventory_order2_product as io2p')
            ->join('product as p', 'p.id', '=', 'io2p.product_id');

        foreach ($wheres as $fields) {
            $query->where($fields->column_name, $fields->operator, $fields->value);
        }

        $query->orderBy('p.id', 'desc');

        return $query;
    }

    public function getProcessingProducts($transaction_id, $supplier_id)
    {
        DB::enableQueryLog();
        $query = $this::select(DB::raw('
            io.id io_id,
            io2p.id io2p_id, io2p.product_id io2p_product_id,
                io2p.price io2p_price, io2p.quantity io2p_quantity,
            c.name c_name,
            p.id p_id, p.name p_name, p.item_code p_item_code, p.description p_desc,
                p.expiration_date,
                p.base_price, p.markup, p.price selling_price'))
            ->from('inventory_order2_product as io2p')
            ->join('product as p', 'p.id', '=', 'io2p.product_id')
            ->join('product_category as c', 'c.id', '=', 'p.category_id')
            ->join('inventory_order as io', 'io.id', '=', 'io2p.transaction_id')
            ->where('io2p.transaction_id', $transaction_id)
            ->where('p.supplier_id', $supplier_id)
            ->whereNull('io2p.status_id');

        // $query->get();
        // print_r(DB::getQueryLog());
        // die();
        return $query->get();
    }

    public function getOrderHistory(
        $page_path,
        $from = "",
        $to = "",
        $paginated = true
    ) {
        $products = self::select(DB::raw('
            io2p.id io2p_id, io2p.transaction_id, io2p.quantity received_quantity, io2p.back_order_quantity,
            p.id p_id, p.item_code, p.category_id, p.stock, p.base_price,
            p.markup, p.price, 
            p.name p_name, p.unit, p.description, p.supplier_id, p.expiration_date'))
            ->from('inventory_order2_product as io2p')
            ->join('product as p', 'p.id', '=', 'io2p.product_id')
            // ->orWhere(function ($query) use ($search) {
            //     $query->where('io2p.id', $search)
            //         ->orWhere('p.item_code', 'LIKE', "%$search%")
            //         ->orWhere('p.name', 'LIKE', "%$search%");
            // })
            ->where('io2p.status_id', STATUS_ORDER_RECEIVED)
            ->when($from && $to, function ($query) use ($from, $to) {
                $time_start = "00:00:00";
                $time_end = "23:59:59";
                $query->where("io2p.date_received", ">=", $from . " $time_start")
                    ->where("io2p.date_received", "<=", $to . " $time_end");
            })
            ->orderBy('io2p.id', 'desc');

        if ($paginated) {
            $products = $products->paginate(Config::get('constant.per_page'))
                ->withPath($page_path)
                ->appends(
                    [
                        'from' => $from,
                        'to' => $to,
                    ]
                );
        }
        return $products;
    }

    public function getBackOrder(
        $page_path,
        $from = "",
        $to = "",
        $q = "",
        $paginated = true
    ) {
        $products = self::select(DB::raw('
            io2p.id io2p_id, io2p.transaction_id, io2p.quantity received_quantity, io2p.back_order_quantity,
            io2p.date_received,
            p.id p_id, p.item_code, p.category_id, p.stock, p.base_price,
            p.markup, p.price, 
            p.name p_name, p.unit, p.description, p.supplier_id, p.expiration_date'))
            ->from('inventory_order2_product as io2p')
            ->join('product as p', 'p.id', '=', 'io2p.product_id')
            ->when($q, function ($query) use ($q) {
                $query->orWhere(function ($query) use ($q) {
                    $query->where('io2p.transaction_id', $q);
                });
            })
            ->where('io2p.status_id', STATUS_ORDER_RECEIVED)
            ->when($from && $to, function ($query) use ($from, $to) {
                $time_start = "00:00:00";
                $time_end = "23:59:59";
                $query->where("io2p.date_received", ">=", $from . " $time_start")
                    ->where("io2p.date_received", "<=", $to . " $time_end");
            })
            ->where('io2p.back_order_quantity', ">", 0)
            ->orderBy('io2p.id', 'desc');

        if ($paginated) {
            $products = $products->paginate(Config::get('constant.per_page'))
                ->withPath($page_path)
                ->appends(
                    [
                        'from' => $from,
                        'to' => $to,
                        'q' => $q,
                    ]
                );
        }
        return $products;
    }
}
