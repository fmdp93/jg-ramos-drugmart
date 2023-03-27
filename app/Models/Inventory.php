<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\InventoryController;
use App\Http\Traits\SearchTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;
    use SearchTrait;


    protected $table = "inventory";

    public $timestamps = false;

    public function getProducts($search, $category_id, $expiry, $page_path, $stock_filter = '')
    {
        DB::enableQueryLog();
        $Products = Inventory::select(
            DB::raw('p.id p_id, p.item_code, p.name p_name,
        p.description, p.price, p.unit, p.expiration_date, p.stock p_stock,
        s.vendor, s.company_name,
        i.id i_id, i.stock i_stock,
            (SELECT SUM(i2.stock) sum_i_stock
                FROM inventory i2
                INNER JOIN product p2
                ON p2.id = i2.product_id
                WHERE p2.item_code = p.item_code
                GROUP BY p2.item_code
                LIMIT 1) sum_i_stock,
        c.name c_name')
        )
            ->from('inventory as i')
            ->leftJoin('product as p', 'i.product_id', '=', 'p.id')
            ->leftJoin('product_category as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('supplier as s', 's.id', '=', 'p.supplier_id')
            ->whereNull('status_id') // Null is default (not archived)
            ->when($category_id, function ($query) use ($category_id) {
                $query->where('c.id', $category_id);
            })
            ->when($stock_filter, function ($query) use ($stock_filter) {
                $this->filter_stock($query, $stock_filter);
            });
        $Products = $this->setWhereSearch(
            $Products,
            $search,
            ['p.name' => '=', 'p.item_code' => '='],
            ['p.name']
        );

        $Products->orderBy('p.expiration_date', $expiry)
            ->orderBy('p.name', 'asc');

        // echo $Products->toSql();
        // die();
        $Products = $Products->paginate(Config::get('constant.per_page'))
            ->withPath($page_path)
            ->appends(
                [
                    'q' => $search,
                    'category_id' => $category_id,
                ]
            )
            ->withQueryString();
        
        // echo '<pre>';
        // print_r(DB::getQueryLog());
        // echo '</pre>';
        // die();
        return $Products;
    }

    public function getHalfStock()
    {
        $Inventory = Inventory::select(
            DB::raw('p.id p_id, p.item_code, p.name p_name,
        p.description, p.price, p.unit, p.expiration_date, p.stock p_stock,
        SUM(i.stock) i_stock,
        c.name c_name')
        )
            ->from('inventory as i')
            ->leftJoin('product as p', 'i.product_id', '=', 'p.id')
            ->leftJoin('product_category as c', 'c.id', '=', 'p.category_id')
            ->whereNull('status_id')
            ->groupBy('p.item_code')
            ->orderBy('p.id', 'desc')
            ->havingRaw('i_stock <= (.5 * p.stock)')
            ->get();

        return $Inventory;
    }

    private function filter_stock($query, $stock_filter)
    {
        if ($stock_filter == 'normal') {
            $query->havingRaw('sum_i_stock > (.5 * p.stock)');
        } else if ($stock_filter == 'half') {
            $query->havingRaw('(sum_i_stock > (.3 * p.stock)) and (sum_i_stock <= (.5 * p.stock))');
        } else if ($stock_filter == 'low') {
            $query->havingRaw('sum_i_stock <= (.3 * p.stock)');
        }
        return $query;
    }

    public function returnStock($id, $quantity)
    {
        Inventory::where('product_id', $id)
            ->update([
                'stock' => DB::raw("stock + $quantity")
            ]);
    }

    public static function getStock($product_id)
    {
        $Inventory = Inventory::where('product_id', $product_id)
            ->first();

        return $Inventory !== null ? $Inventory->stock : 0;
    }

    public static function getStockOf($item_code)
    {
        $stock = Inventory::select(DB::raw('SUM(i.stock) max_stock'))
            ->from("inventory as i")
            ->join("product as p", 'p.id', '=', 'i.product_id')
            ->where('p.item_code', $item_code)
            ->whereNull('deleted_at')
            ->groupBy('p.item_code')
            ->first();

        return $stock !== null ? $stock->max_stock : 0;
    }

    public function getArchivedInvItems($search, $page_path)
    {
        $Archives = Inventory::select(
            DB::raw('p.id p_id, p.item_code, p.name p_name,
        p.description, p.price, p.unit, p.expiration_date, p.stock p_stock,
        i.id i_id, i.stock i_stock,
        c.name c_name')
        )
            ->from('inventory as i')
            ->leftJoin('product as p', 'i.product_id', '=', 'p.id')
            ->leftJoin('product_category as c', 'c.id', '=', 'p.category_id')
            ->orWhere(function ($query) use ($search) {
                $query->where('p.item_code', 'LIKE', "%$search%")
                    ->orWhere('p.name', 'LIKE', "%$search%");
            })
            ->where('status_id', 16) // Archived = 16
            ->orderBy('i.id', 'desc')
            ->paginate(Config::get('constant.per_page'))
            ->withPath($page_path)
            ->appends(
                [
                    'q' => $search,
                ]
            );


        return $Archives;
    }

    public function getInventoryReport($page_path, $from = "", $to = "", $paginated = true)
    {
        DB::enableQueryLog();
        $Products = Inventory::select(
            DB::raw('
                pt2p.pos_transaction_id, 
                p.name p_name,
                il.updated_quantity,
                (SELECT GROUP_CONCAT(
                            CONCAT(p2.name, " (", pt2p2.remark, ")")
                        ) 
                    FROM pos_transaction2product pt2p2
                    INNER JOIN product p2
                        ON p2.id = pt2p2.product_id
                    WHERE pt2p2.pos_transaction_id = pt2p.pos_transaction_id
                        && pt2p2.remark != ""
                    GROUP BY pt2p2.pos_transaction_id
                ) as returns')
        )
            ->from('pos_transaction2product as pt2p')
            ->join('product as p', 'p.id', '=', 'pt2p.product_id')
            ->join('inventory_log as il', 'il.pt2p_id', '=', 'pt2p.id')
            ->join('pos_transaction as pt', 'pt.id', '=', 'pt2p.pos_transaction_id')
            ->when($from && $to, function ($query) use ($from, $to) {
                $time_start = "00:00:00";
                $time_end = "23:59:59";
                $query->where("pt.created_at", ">=", $from . " $time_start")
                    ->where("pt.created_at", "<=", $to . " $time_end");
            })
            ->groupBy('pt2p.pos_transaction_id')
            ->orderBy('pt2p.pos_transaction_id', 'desc');
        // ->when($paginated, function ($query) use ($page_path) {
        //     $query->paginate(Config::get('constant.per_page'))
        //         ->withPath($page_path);
        // });

        if ($paginated === true) {
            $Products = $Products->paginate(Config::get('constant.per_page'))
                ->withPath($page_path)
                ->appends(
                    [
                        'from' => $from,
                        'to' => $to,
                    ]
                );;
        }

        // $Products->get();
        // echo '<pre>';
        // print_r(DB::getQueryLog());
        // echo '</pre>';
        // die();
        return $Products;
    }

    public function getReportFor($transaction_id)
    {
        DB::enableQueryLog();
        $Products = Inventory::select(
            DB::raw(
                '
                pt2p.pos_transaction_id, pt2p.quantity pt2p_quantity, 
                pt2p.refunded_quantity, 
                p.name p_name, p.item_code,
                il.updated_quantity,
                pt2p.remark'
            )
        )
            ->from('pos_transaction2product as pt2p')
            ->join('product as p', 'p.id', '=', 'pt2p.product_id')
            ->join('inventory_log as il', 'il.pt2p_id', '=', 'pt2p.id')
            ->where('pt2p.pos_transaction_id', $transaction_id)
            ->orderBy('pt2p.id', 'asc');

        // $Products->get();
        // echo '<pre>';
        // print_r(DB::getQueryLog());
        // echo '</pre>';
        // die();
        return $Products;
    }

    public static function getProductFromRequest($request)
    {
        DB::enableQueryLog();
        $inventory = Inventory::select(
            DB::raw("i.stock i_stock,
            p.id p_id, p.item_code, p.name p_name,
        p.description, p.price, p.unit, p.expiration_date, p.stock p_stock,
        c.name c_name")
        )
            ->from('pos_transaction2product as pt2p')
            ->join('product as p', 'p.id', '=', 'pt2p.product_id')
            ->join('inventory as i', 'i.product_id', '=', 'pt2p.product_id')
            ->join('product_category as c', 'c.id', '=', 'p.category_id');

        if ($request->input('transaction_id')) {
            $inventory = $inventory->where('pos_transaction_id', $request->input('transaction_id'));
        }

        if ($request->input('item_code')) {
            $inventory = $inventory->where('p.item_code', $request->input('item_code'));
        }

        if ($request->input('item_name')) {
            $inventory = $inventory->where('p.name', 'LIKE', "%" . $request->input('item_name') . "%")
                ->where('p.name', '!=', '');
        }

        if (
            $request->input('transaction_id') == "" &&
            $request->input('item_name') == "" &&
            $request->input('item_code') == ""
        ) {
            $inventory = $inventory->whereRaw('NULL');
        }

        return $inventory;
    }
}
