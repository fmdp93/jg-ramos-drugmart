<?php

namespace App\Models;

use App\Http\Traits\SearchTrait;
use Illuminate\Support\Facades\DB;
use Database\Factories\ProductFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SearchTrait;

    protected $table = 'product';

    public $timestamps = false;

    protected static function newFactory()
    {
        return ProductFactory::new();
    }

    /**
     * Add stock to new inventory item or just update the existing inventory
     * 
     * @return $inventory_id
     */

    public static function addStock($product_id, $quantity)
    {
        $Inventory = Inventory::where('product_id', $product_id)
            ->whereNull('status_id')
            ->get();

        if (count($Inventory) === 0) {
            // insert              
            $Inventory = new Inventory();
            $Inventory->product_id = $product_id;
            $Inventory->stock = $quantity;
            $Inventory->save();
            $inventory_id = $Inventory->id;
        } else {
            // update
            $Inventory = new Inventory();
            $Inventory = $Inventory->where('product_id', $product_id);
            $inventory_id = $Inventory->first()->id;

            $Inventory->update([
                'stock' => DB::raw('stock + ' . $quantity)
            ]); // returns 1 after updating
        }

        return $inventory_id;
    }

    public function getProducts($search, $category_id, $page_path)
    {
        $Products = Product::select(
            DB::raw('p.id p_id, p.item_code, p.name p_name,
                p.description, c.name c_name, 
                p.price, p.markup, p.base_price, p.stock, p.unit, p.expiration_date')
        )
            ->from('product as p')
            ->leftJoin('product_category as c', 'p.category_id', '=', 'c.id')
            ->when($category_id, function ($query) use ($category_id) {
                $query->where('c.id', $category_id);
            })
            ->where('p.deleted_at', null);
        $Products = $this->setWhereSearch(
            $Products,
            $search,
            ['p.item_code' => '=', 'p.name'  => '='],
            ['p.name', 'p.description']
        );

        $Products = $Products->orderBy('p.id', 'desc')
            ->paginate(Config::get('constant.per_page'))
            ->withPath($page_path)
            ->appends(
                [
                    'q' => $search,
                    'category_id' => $category_id,
                ]
            )
            ->withQueryString();

        // echo $Products->toSql();
        // DB::enableQueryLog();
        // $Products->get();        
        // print_r(DB::getQueryLog());
        // die();

        return $Products;
    }

    public static function getProduct($id)
    {
        // $product = new Product();
        // var_dump($id);

        return Product::select(DB::raw("p.id p_id,
            s.id s_id, s.vendor, s.company_name, s.contact_detail, s.address"))
            ->from("product as p")
            ->leftJoin("supplier as s", "s.id", "=", "p.supplier_id")
            ->where("p.id", $id);
    }

    public static function byItemCode($item_code)
    {
        // $product = new Product();
        // var_dump($id);

        return Product::select(DB::raw("MAX(p.id) p_id,
            p.name p_name, p.description, p.price, p.base_price, p.markup, p.unit, 
            p.stock, p.expiration_date,
            c.id c_id, c.name c_name,
            s.id s_id, s.vendor, s.company_name, s.contact_detail, s.address"))
            ->from("product as p")
            ->leftJoin('product_category as c', 'c.id', '=', 'p.category_id')
            ->leftJoin("supplier as s", "s.id", "=", "p.supplier_id")
            ->where("p.item_code", $item_code)
            ->groupBy("p.item_code")
            ->first();
    }
}
