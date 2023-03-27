<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Inventory;
use App\Models\ProductLog;
use App\Rules\IntNoNegative;
use Illuminate\Http\Request;
use App\Rules\FloatNoNegative;
use App\Http\Traits\ProductsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    use ProductsTrait;
    public static $page_path = '/products';
    public static $product_search_url = '/product/search';

    public function index(Request $request)
    {
        $data['heading'] = 'PRODUCTS';
        $data['categories'] = Category::whereNull('deleted_at')->orderBy('name')->get();
        $data['search'] = $request->input('q');
        $data['category_id'] = $request->input('category_id');
        $ProductModel = new Product();
        $data['products'] = $ProductModel->getProducts($data['search'], $data['category_id'], self::$page_path);
        $data['form_id'] = "update_product_form";
        $data['d_none'] = empty(count($data['products'])) ?: 'd-none';
        $data['action'] = action([ProductsController::class, 'delete']);
        $data['action_print_barcode'] = action([ProductsController::class, 'printBarcode']);        

        return view('pages.admin.products', $data);
    }

    public function addProduct()
    {
        $data['heading'] = 'Add Products';
        $data['categories'] = Category::whereNull('deleted_at')->orderBy('name', 'asc')->get();
        $data['markup'] = empty(old('markup')) ? Config::get('app.markup_price') : old('markup');                
        $data['suppliers'] = Supplier::whereNull('deleted_at')
            ->orderBy('vendor', 'asc')
            ->get();

        return view('pages.admin.add-product', $data);
    }

    /**
     * Async request
     * 
     * @return Response()->json()
     */

    public function getItemCodeDetails(Request $request){
        $item_code = $request->input('item_code');
        DB::enableQueryLog();
        $product = Product::byItemCode($item_code);

        $response = [
            'product' => $product,
            'last_query' => DB::getQueryLog(),
        ];

        $response = json_encode($response);

        return Response()->json($response);
    }

    public function getNewItemCode(Request $request)
    {
        $response['new_item_code'] = $this->getNewBarcode();
        return Response()->json(json_encode($response));
    }

    private function getNewBarcode()
    {
        do {
            $num1 = rand(100000, 999999);
            $num2 = rand(100000, 999999);
            $barcode = "$num1$num2";
            $products = Product::where('item_code', $barcode);
            if (count($products->get()) == 0) {
                return $barcode;
            }
        } while (1);
    }

    public function updateProduct(Request $request)
    {
        $query_string = "?q=" . $request->input('search');
        $query_string .= "&page=" . $request->input('page');
        $query_string .= "&category_id=" . $request->input('category_id');

        // dd($query_string);
        $input = $request->input();
        $rules = [
            'product_id' => 'required',
            'expiration_date' => 'required|date',
            'base_price' => ['required', 'numeric', 'min:0'],                           
            'markup' => ['required', 'numeric', 'min:0'],            
            'selling_price' => ['required', 'numeric', 'min:0'], 
            'supplier_search_id' => 'required|numeric|min:1',
        ];

        $messages = [
            'product_id.required' => 'Please pick a product first',
            'supplier_search_id.required' => 'Supplier is required',
        ];

        $validator = Validator::make($input, $rules, $messages);
        if ($validator->fails()) {
            return redirect('/products')
                ->withErrors($validator)
                ->withInput();
        }

        Product::where('id', $request->input('product_id'))
            ->update(
                [
                    'expiration_date' => $request->input('expiration_date'),
                    'base_price' => $request->input('base_price'),                    
                    'markup' => $request->input('markup'),
                    'price' => $request->input('selling_price'),
                    'supplier_id' => $request->input('supplier_search_id'),
                ],
            );

        ProductLog::log($request->input('product_id'), 6); // 6 for product edited;

        $request->session()->flash('msg_success', 'Product updated successfully!');


        return redirect('/products' . $query_string)
            ->withInput();
    }
    /**
     * Adds a product to the database.
     * (Optional) adds product to the inventory.
     * 
     * @return redirect
     */

    public function store(Request $request)
    {
        $input = $request->input();
        $rules = [
            'item_code' => 'required',
            'name' => 'required',
            'description' => 'required',
            'category_id' => 'required|integer',
            'base_price' => ['required', 'numeric', 'min:0'],                        
            'markup' => ['required', 'numeric', 'min:0'],            
            'selling_price' => ['required', 'numeric', 'min:0'],            
            'unit' => 'required',
            'stock' => ['required', 'integer', 'min:1'],
            'expiration_date' => 'required|date',
            'inv_stock' => 'nullable|numeric|min:0',
            'supplier' => 'required|integer|min:1',
        ];
        $messages = [
            'item_code.integer' => 'Item code must be a valid barcode number',
            'supplier.required' => 'Supplier is required',
        ];

        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)
                ->withInput();
        }

        $Product = new Product();
        $Product->item_code = $request->input('item_code');
        $Product->name = $request->input('name');
        $Product->description = $request->input('description');
        $Product->category_id = $request->input('category_id');
        $Product->base_price = $request->input('base_price');        
        $Product->markup = $request->input('markup');
        $Product->price = $request->input('selling_price');
        $Product->unit = $request->input('unit');
        $Product->stock = $request->input('stock');
        $Product->supplier_id = $request->input('supplier');
        $Product->expiration_date = $request->input('expiration_date');

        $Product->save();
        ProductLog::log($Product->id, 5); // 5 for product added;

        // Add to inventory if inventory stock is present
        if ($request->input('inv_stock') >= 0 && $request->input('inv_stock') !== null) {
            $product_id = $Product->id;
            $Inventory = new Inventory();
            $Inventory->product_id = $product_id;
            $Inventory->stock = $request->input('inv_stock');                        
            $Inventory->save();
        }

        $request->session()->flash('msg_success', 'Product added successfully!');
        return redirect()->action([ProductsController::class, 'addProduct']);
    }

    public function delete(Request $request)
    {
        $product_id = $request->input('product_id');
        Product::where('id', $product_id)
            ->update(
                ['deleted_at' => date('Y-m-d H:i:s')]
            );

        ProductLog::log($product_id, 7); // 7 for product deleted;
        $request->session()->flash('msg_success', 'Product deleted successfully!');

        return redirect('/products');
    }

    public function getProduct($id){
        DB::enableQueryLog();
        $product =  Product::getProduct($id);
        $product = $product->first();
        $q = DB::getQueryLog();
        $response = [
            'q' => $q,
            'supplier' => $product,
        ];
        $response = json_encode($response);
        // $response = json_encode($product->getProduct($id));
        return Response()->json($response);
    }
}
