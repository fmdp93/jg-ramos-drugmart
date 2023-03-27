<?php

namespace App\Http\Controllers;

use stdClass;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Inventory;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use App\Models\InventoryOrder;
use App\Http\Traits\SearchTrait;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryOrder2Product;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StorePurchaseOrderRequest;

class InventoryController extends Controller
{
    use SearchTrait;
    private $tbody_content;
    public static $page_path = '/inventory';

    public function index(Request $request)
    {
        $data['heading'] = 'INVENTORY';
        // Get parameters
        $data['categories'] = Category::whereNull('deleted_at')->orderBy('name')->get();
        $data['search'] = $request->input('q');
        $stock_filter = $request->input('stock_filter');
        $data['category_id'] = $request->input('category_id');
        $data['expiry'] = $request->input('expiry');
        $data['archive_action'] = route('inventory_archive');
        // EOQ
        $Inventory = new Inventory();
        $data['half_stock_products'] = $Inventory->getHalfStock();

        // Inventory        
        $expiry = getExpiryOrderBy($request->input('expiry'));
        $data['products'] = $Inventory->getProducts($data['search'], $data['category_id'], $expiry, self::$page_path, $stock_filter);
        $data['d_none'] = count($data['products']) ? 'd-none' : '';
        $data['form'] = 'purchase-order';
        $data['product_search_url'] = "/inventory/search";
        $data['show_action'] = true;
        $data['content'] = 'admin_content';
        $data['components_content'] = 'components.admin.content';

        return view('pages.admin.inventory', $data);
    }
    /**
     * Archive a product in the inventory
     * 
     * @return redirect
     */
    public function archive(Request $request)
    {
        $inventory_id = $request->input('inventory_id');
        // sets url params
        $url_params = $request->input('url_params');
        $url_params = $url_params ? "?$url_params" : "";

        Inventory::where('id', $inventory_id)
            ->update(['status_id' => 16]); // 16 is archived
        $request->session()->flash('msg_success', 'Product archived succesfully!');
        $back = action([InventoryController::class, 'index']) . $url_params;
        return redirect($back);
    }
    /**
     * List of archived products from inventory
     */
    public function archives(Request $request)
    {
        $search = $request->input('q');

        $data['heading'] = "Inventory Archive";
        $data['title'] = "Inventory Archive";
        $data['search'] = $search;
        $data['unarchive_inv_item_action'] = route('inventory_unarchive');

        $Inventory = new Inventory();
        $data['archive_inv_items'] = $Inventory->getArchivedInvItems($search, '/inventory/archives');
        $data['d_none'] = count($data['archive_inv_items']) ? 'd-none' : '';

        return view('pages.admin.archives', $data);
    }

    /**
     * 
     */
    public function archiveSearch(Request $request)
    {
        $search = $request->input('q');

        $Inventory = new Inventory();

        DB::enableQueryLog();
        $data['archive_inv_items'] = $Inventory->getArchivedInvItems($search, '/inventory/archives');
        $data['unarchive_inv_item_action'] = $request->input('unarchive_inv_item_action');
        $data['search'] = "$search";
        $rows = (string) view("components.admin.archive_inv_item-list", $data);

        $data['d_none'] = count($data['archive_inv_items']) ? 'd-none' : '';
        $table_empty = (string) view("layouts.empty-table", $data);
        $links = (string) $data['archive_inv_items']->links();
        $row_count = count($data['archive_inv_items']);
        $response = [
            'rows_html' => $rows,
            'links_html' => $links,
            'table_empty' => $table_empty,
            'row_count' => $row_count,
            'last_query' => DB::getQueryLog(),
        ];
        $response = json_encode($response);
        return Response()->json($response);
    }

    public function unarchive(Request $request)
    {
        Inventory::where('id', $request->input('archive_inv_item_id'))
            ->update([
                'status_id' => null,
            ]);

        $params = [
            'q' => $request->input('search'),
            'page' => $request->input('page'),
        ];

        $request->session()->flash('msg_success', 'Unarchive succesful!');
        $back = route('inventory_archives', $params);
        // dd($back);
        return redirect($back);
    }

    public function orders(Request $request)
    {
        $data['heading'] = 'Inventory Orders';
        $InventoryOrder = new InventoryOrder();
        $data['inventory_orders'] = $InventoryOrder->getProcessing();

        return view('pages.admin.inventory-orders', $data);
    }

    public function getInventoryOrderProcessing(Request $request)
    {
        $InventoryOrder = new InventoryOrder();
        $data['inventory_orders'] = $InventoryOrder->getProcessing();

        $view = (string) view('components.admin.inventory-orders-list', $data);
        $response = [
            'inventory_orders_list' => $view,
        ];
        $response = json_encode($response);
        return Response()->json($response);
    }

    /**
     * Gets Inventory Order product details
     * 
     * @return Response()->json($response);
     */
    public function orderProducts(Request $request)
    {
        $io_id = $request->input('io_id');
        $supplier_id = $request->input('supplier_id');
        $InventoryOrder2Product = new InventoryOrder2Product();
        $data['io_products'] = $InventoryOrder2Product->getProcessingProducts($io_id, $supplier_id);
        $response['modal_content'] = (string) view('components.io2p-rows', $data);

        $response = json_encode($response);
        return Response()->json($response);
    }

    public function purchaseOrder(Request $request)
    {
        $data['heading'] = 'Purchase Order Form';
        $data['suppliers'] = Supplier::whereNull('deleted_at')
            ->orderBy('vendor', 'asc')
            ->get();

            if ($request->has('backorder')) {
                $backorder = true;
            } else {
                $backorder = false;
            }

        $this->setHalfStockTbodyContent($request, $backorder);

        $this->setLastTableContent($request);

        $data['tbody_content'] = $this->tbody_content;
        $data['d_none'] = !empty($data['tbody_content']) ? 'd-none' : '';

        return view('pages.admin.purchase-order', $data);
    }

    private function setHalfStockTbodyContent($request)
    {
        if ($request->input('request_half_stock') == 1 && empty(old())) {
            $Inventory = new Inventory();
            $products = $Inventory->getHalfStock();

            if (!empty($products)) {
                foreach ($products as $product) {
                    $data['form'] = 'purchase-order';
                    $data['p_id'] = $product->p_id;
                    $data['code'] = $product->item_code;
                    $data['name'] = $product->p_name;
                    $data['description'] = $product->description;
                    $data['quantity'] = $product->p_stock - $product->i_stock;
                    $data['price'] = $product->price;
                    $data['subtotal'] = $data['price'] * $data['quantity'];
                    $this->tbody_content .= (string) view("components.purchase-order-row", $data);
                }
            }
            ($this->tbody_content);
        }
    }

    private function setLastTableContent($request)
    {
        if (!empty($request->old('product_id'))) {
            foreach ($request->old('product_id') as $key => $product_id) {
                $data['p_id'] = $request->old('product_id')[$key];
                $data['code'] = $request->old('t_item_code')[$key];
                $data['name'] = $request->old('t_name')[$key];
                $data['description'] = $request->old('t_description')[$key];
                $data['quantity'] = $request->old('quantity')[$key];
                $data['price'] = $request->old('price')[$key];
                $data['subtotal'] = $data['price'] * $data['quantity'];
                $data['form'] = 'purchase-order';
                $this->tbody_content .= (string) view("components.purchase-order-row", $data);
            }
        }
    }

    public function orderReceived(Request $request)
    {
        $quantity = $request->input('quantity');
        $product_id = $request->input('product_id');

        $new_product_id = $this->newProductIfNotSame();
        $product_id = $new_product_id ? $new_product_id : $product_id;


        $previous_quantity = Inventory::getStock($product_id);

        // new or update inventory item
        $inventory_id = Product::addStock($product_id, $quantity);

        $updated_quantity = Inventory::find($inventory_id)->stock;

        $this->partialReceive($quantity, $product_id);

        DB::enableQueryLog();

        $this->saveDateDelivered();

        // Log
        InventoryLog::log(
            $inventory_id,
            $previous_quantity,
            $updated_quantity,
            8, // 8 for add stock
            null
        );

        $response = [];
        $response = json_encode($response);
        return Response()->json(($response));
    }

    private function partialReceive($quantity, $product_id)
    {
        $io2p_id = request()->input('io2p_id');
        $this->price = request()->input('price');
        $io2p = InventoryOrder2Product::find($io2p_id);
        /**
         * if all item is not received, create a new 
         * inventory_order2product row with the amount received, deduct that
         * to the amount where it came from
         */

        if ($quantity < $io2p->quantity) {
            $new_io2p = new InventoryOrder2Product();
            $new_io2p->transaction_id = $io2p->transaction_id;
            $new_io2p->product_id = $product_id;
            $new_io2p->quantity = $quantity;
            $new_io2p->price = $this->price;
            $new_io2p->status_id = STATUS_ORDER_RECEIVED;
            $new_io2p->date_received = date('Y-m-d H:i:s');

            $new_io2p->save();

            // deduct io2p quantity
            InventoryOrder2Product::where('id', $io2p_id)
                ->update([
                    'quantity' => DB::raw("quantity - $quantity"),
                ]);
        } else {
            // Order received remaining quantity for existing io2p    
            InventoryOrder2Product::where('id', $io2p_id)
                ->update([
                    'quantity' => $quantity,
                    'price' => $this->price,
                    'date_received' => date('Y-m-d H:i:s'),
                    'status_id' => STATUS_ORDER_RECEIVED,
                ]);
        }
    }

    private function saveDateDelivered()
    {
        $transaction_id = request()->input('transaction_id');
        // if all products delivered, save date_delivered to InventoryOrder
        $InventoryOrder2Product = InventoryOrder2Product::where('transaction_id', $transaction_id)
            ->whereNull('status_id');

        if (count($InventoryOrder2Product->get()) == 0) {
            InventoryOrder::where('id', $transaction_id)
                ->update([
                    'date_delivered' => date('Y-m-d'),
                ]);
        }
    }

    /**
     * Create a new product if expiration or price is not the same
     * 
     * @return $product_id
     */

    private function newProductIfNotSame()
    {
        $input_expiration_date = request()->input('expiration_date');
        $item_code = request()->input('item_code');
        $input_price = request()->input('price');
        $wheres = [
            (object) [
                'column_name' => 'item_code',
                'operator' => '=',
                'value' => $item_code,
            ],
            (object) [
                'column_name' => 'expiration_date',
                'operator' => '=',
                'value' => $input_expiration_date,
            ],
        ];
        $io2p = InventoryOrder2Product::getOrderedProduct($wheres)->first();
        $existing_product_expiration_date = "";
        if ($io2p !== null) {
            $existing_product_expiration_date = $io2p->expiration_date;
            $existing_product_price = $io2p->price; //product base price
        }

        // different expiration, new item to product
        if (
            $input_expiration_date != $existing_product_expiration_date
            || $existing_product_price != $input_price
        ) {
            $wheres = [
                (object) [
                    'column_name' => 'item_code',
                    'operator' => '=',
                    'value' => $item_code,
                ],
            ];
            DB::enableQueryLog();
            $io2p = InventoryOrder2Product::getOrderedProduct($wheres)->first();
            // print_r(DB::getQueryLog());
            // die();
            $Product = new Product();
            $Product->item_code = $io2p->item_code;
            $Product->category_id = $io2p->category_id;
            $Product->stock = $io2p->stock;
            $Product->base_price = $input_price;
            $Product->markup = $io2p->markup;
            $Product->price = increaseNumByPercent($input_price, $io2p->markup);
            $Product->name = $io2p->name;
            $Product->unit = $io2p->unit;
            $Product->description = $io2p->description;
            $Product->supplier_id = $io2p->supplier_id;
            $Product->expiration_date = $input_expiration_date;
            $Product->save();

            return $Product->id;
        }
    }

    public function orderCancel(Request $request)
    {
        $inventory_order_id = $request->input('io_id');
        InventoryOrder::where('id', $inventory_order_id)
            ->delete();
        InventoryOrder2Product::where('transaction_id', $inventory_order_id)
            ->delete();
        $request->session()->flash('msg_success', 'Order canceled successfully!');
        return back();
    }
    /**
     * Order products for inventory
     * 
     * @return redirect
     */
    public function store(StorePurchaseOrderRequest $request)
    {
        $request->validated();

        $InventoryOrder = new InventoryOrder();

        $InventoryOrder->vendor = $request->input('vendor');
        $InventoryOrder->company_name = $request->input('company');
        $InventoryOrder->contact_detail = $request->input('contact');
        $InventoryOrder->address = $request->input('address');
        $InventoryOrder->eta = $request->input('eta');
        $InventoryOrder->action_id = 1; // 1 = Order Processing

        $InventoryOrder->save();

        $transaction_id = $InventoryOrder->id; // id of latest insert

        $InventoryOrder2Product = new InventoryOrder2Product();
        $InventoryOrder2Product->insert_products($request, $transaction_id);


        $message = 'Purchase order submitted!';

        $request->session()->flash('msg_success', $message);
        $request->session()->forget('get_half_stock');
        return back();
    }

    public function inventorySearch(Request $request)
    {
        $search = $request->input('q');
        $category_id = $request->input('category_id');
        $stock_filter = $request->input('stock_filter');
        $expiry_filter = $request->input('expiry');
        $data['archive_action'] = $request->input('archive_action');
        $expiry = getExpiryOrderBy($request->input('expiry'));

        $Inventory = new Inventory();

        DB::enableQueryLog();
        $data['products'] = $Inventory->getProducts($search, $category_id, $expiry, self::$page_path, $stock_filter);
        $data['show_action'] = true;

        // this variable is use for redirection after an action like delete
        $data['url_params'] = "q=$search&category_id=$category_id&stock_filter=$stock_filter&expiry=$expiry_filter";
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

    public function purchaseOrderSearch(Request $request)
    {
        $tbody_content = '';
        $item_code = $request->input('item_code');
        DB::enableQueryLog();
        $product = Inventory::select(
            DB::raw('p.id p_id, p.item_code, p.name p_name,
        p.description, p.base_price, p.unit, p.expiration_date, p.stock p_stock,
        c.name c_name,
        s.vendor, s.company_name, s.contact_detail, s.address')
        )
            ->from('product as p')
            ->leftJoin('product_category as c', 'p.category_id', '=', 'c.id')
            ->join('supplier as s', 's.id', '=', 'p.supplier_id')
            ->where('p.item_code', $item_code)
            ->whereNull('p.deleted_at')
            ->whereIn('p.id', function ($query) {
                $query->select(DB::raw('max(id)'))
                    ->from('product')
                    ->groupBy('item_code');
            })
            ->first();


        $tbody_content = $this->getPurchasOrderRow($product, request()->input('quantity'));

        $params['result'] = $product;
        $params['tbody'] = $tbody_content;
        return $this->getPurcOrdResponse($params);
    }

    public function purchaseOrderSupplierSearch(Request $request)
    {
        $tbody_content = '';
        $supplier_id = $request->input('supplier_id');
        DB::enableQueryLog();
        $products = Inventory::select(
            DB::raw('
                ((SELECT stock max_stock FROM product 
                    WHERE item_code = p.item_code order by id desc limit 1) 
                    - SUM(IF(i.stock is NULL, 0, i.stock))) order_quantity,
                p.id p_id, p.item_code, p.name p_name,
                p.description, p.base_price, p.unit, p.expiration_date, p.stock p_stock,
                c.name c_name,
                s.vendor, s.company_name, s.contact_detail, s.address')
        )
            ->from('product as p')
            ->leftJoin('product_category as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('inventory as i', 'i.product_id', '=', 'p.id')
            ->join('supplier as s', 's.id', '=', 'p.supplier_id')
            ->where('p.supplier_id', $supplier_id)
            ->whereNull('p.deleted_at')
            ->whereNull('i.status_id') //not archived
            ->whereIn('p.id', function ($query) {
                $query->select(DB::raw('max(id)'))
                    ->from('product')
                    ->groupBy('item_code');
            })
            ->having('order_quantity', '>', 0)
            ->groupBy('p.item_code');

        foreach ($products->get() as $product) {
            $tbody_content .= $this->getPurchasOrderRow($product, $product->order_quantity);
        }

        $params = [
            'tbody' => $tbody_content,
            'result' => $product,
        ];
        return $this->getPurcOrdResponse($params);
    }

    private function getPurcOrdResponse($params)
    {
        $response = [
            'table_empty' => (string) view('layouts.empty-table'),
            'last_query' => DB::getQueryLog(),
        ];

        $response = array_merge($response, $params);

        $response = json_encode($response);
        return Response()->json($response);
    }

    private function getPurchasOrderRow($product, $quantity)
    {
        if (!empty($product)) {
            $data['p_id'] = $product->p_id;
            $data['code'] = $product->item_code;
            $data['name'] = $product->p_name;
            $data['description'] = $product->description;
            $data['quantity'] = $quantity;
            $data['price'] = $product->base_price;
            $data['subtotal'] = $data['price'] * $data['quantity'];
            $data['form'] = 'purchase-order';
            return $tbody_content = (string) view("components.purchase-order-row", $data);
        }
    }

    public function orderHistory(Request $request)
    {
        $from = urldecode($request->input('from'));
        $to = urldecode($request->input('to'));

        $data['heading'] = "Inventory Order History";
        $data['title'] = "Inventory Order History";

        $InventoryOrder2Product = new InventoryOrder2Product();
        $data['products'] = $InventoryOrder2Product->getOrderHistory(URI_INV_ORDER_HISTORY, $from, $to);
        $data['d_none'] = count($data['products']) ? 'd-none' : '';

        return view('pages.admin.order-history', $data);
    }

    public function searchOrderHistory(Request $request)
    {

        $InventoryOrder2Product = new InventoryOrder2Product();

        DB::enableQueryLog();
        $data['products'] = $InventoryOrder2Product->getOrderHistory(URI_INV_ORDER_HISTORY);
        $rows = (string) view("components.admin.order-history-list", $data);

        $data['d_none'] = count($data['products']) ? 'd-none' : '';
        $table_empty = (string) view("layouts.empty-table", $data);
        $links = (string) $data['products']->links();
        $row_count = count($data['products']);
        $response = [
            'rows_html' => $rows,
            'links_html' => $links,
            'table_empty' => $table_empty,
            'row_count' => $row_count,
            'last_query' => DB::getQueryLog(),
        ];
        $response = json_encode($response);
        return Response()->json($response);
    }

    public function print_inventory_order_report(Request $request)
    {

        $from = urldecode($request->input('from'));
        $to = urldecode($request->input('to'));
        DB::enableQueryLog();
        $data['heading'] = "Inventory Order History";
        $data['title'] = "Inventory Order History";
        $data['from'] = $from ? date("F j, Y", strtotime($from)) : 'start';
        $data['to'] = $to ? date("F j, Y", strtotime($to)) : 'end';

        $InventoryOrder2Product = new InventoryOrder2Product();
        $data['products'] = $InventoryOrder2Product->getOrderHistory(
            URI_INV_ORDER_HISTORY,
            $from,
            $to,
            false
        )->get();
        $data['d_none'] = count($data['products']) ? 'd-none' : '';

        $view = (string) view('pages.admin.print-order-history', $data);
        // return $view;
        $options = new Options();
        $dompdf = new Dompdf();
        $publicPath = base_path('public/');
        $current_options = $dompdf->getOptions();
        $options->set('chroot', $publicPath);
        $options->set('fontDir', $publicPath);

        $dompdf->setPaper('letter', 'landscape');
        $dompdf->setOptions($options);
        $dompdf->setBasePath(base_path('public/'));

        $dompdf->loadHTML($view);
        $dompdf->render();

        $date_range = $from && $to ? "$from-to-$to-" : "";
        $dompdf->stream("{$date_range}inventory-order-report");
    }
}
