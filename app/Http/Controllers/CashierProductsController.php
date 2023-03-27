<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Http\Traits\ProductsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashierProductsController extends Controller
{
    use ProductsTrait;
    public static $page_path = '/cashier-products';
    public static $product_search_url = '/cashier/products/search';

    public function index(Request $request){
        $data['heading'] = 'PRODUCTS';
        $data['categories'] = Category::whereNull('deleted_at')->orderBy('name')->get();
        $data['search'] = $request->input('q');
        $data['category_id'] = $request->input('category_id');
        $data['expiry'] = $request->input('expiry');        

        $Inventory = new Inventory();
        $data['half_stock_products'] = $Inventory->getHalfStock();

        $expiry = getExpiryOrderBy($request->input('expiry'));        
        $data['products'] = $Inventory->getProducts($data['search'], $data['category_id'], $expiry, self::$page_path);
        
        $data['form_id'] = "products";
        $data['d_none'] = empty(count($data['products'])) ?: 'd-none';
        $data['page_path'] = self::$page_path;
        $data['product_search_url'] = "/cashier/products/search";
        $data['show_action'] = false;
        $data['content'] = 'cashier_content';
        $data['components_content'] = 'components.cashier.content';

        return view('pages.cashier.inventory', $data);
    }

    public function search(Request $request)
    {
        $search = $request->input('q');
        $category_id = $request->input('category_id');
        $expiry_filter = $request->input('expiry');         
        $expiry = getExpiryOrderBy($request->input('expiry'));

        $data['url_params'] = "q=$search&category_id=$category_id&expiry=$expiry_filter";
        $Inventory = new Inventory();
        DB::enableQueryLog();
        $data['products'] = $Inventory->getProducts($search, $category_id, $expiry, self::$page_path);
        $data['show_action'] = false;
        $rows = (string) view("components.inventory-list", $data);
        $data['d_none'] = count($data['products']) ? 'd-none' : '';
        $table_empty = (string) view("layouts.empty-table", $data);
        $links = (string) $data['products']->links();
        $row_count = count($data['products']);
        $response = [
            'rows_html' => $rows,
            'links_html' => $links,
            'table_empty' => $table_empty,
            'row_count' => $row_count,
            'table_color' => $request->input('table_color'),
            'last_query' => DB::getQueryLog(),            
        ];
        $response = json_encode($response);
        return Response()->json($response);
    }
}
