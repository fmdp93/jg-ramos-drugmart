<?php

namespace App\Http\Controllers;

use PDF as PDF2;
use Dompdf\Dompdf;
// use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Role;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\ConfigModel;
use PosTransaction2Product;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use App\Http\Traits\UserTrait;
use App\Http\Requests\RRRequest;
use Illuminate\Support\Facades\DB;
use App\Models\POSTransactionModel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
use \Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Http\Requests\POSCheckoutRequest;
use App\Http\Traits\SearchTrait;
use App\Models\POSTransaction2ProductModel;

class POSController extends Controller
{
    use UserTrait;
    use SearchTrait;
    private $tbody_content;
    public function index(Request $request)
    {
        $data['heading'] = 'POINT OF SALES';
        $data['title'] = 'POS';
        $data['form'] = 'pos';
        $this->setUserContent($data);
        $data['senior_discount'] = ConfigModel::find(3)->value; // 3 for senior discount

        $this->setLastTableContent($request, $data['form']);

        $data['tbody_content'] = $this->tbody_content;
        $data['d_none'] = !empty($data['tbody_content']) ? 'd-none' : '';

        return view('pages.cashier.pos', $data);
    }

    private function setLastTableContent($request, $form)
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
                $data['form'] = $form;
                $this->tbody_content .= (string) view("components.pos-row", $data);
            }
        }
    }

    public function checkout(POSCheckoutRequest $request)
    {
        $validated = $request->validated();

        // make transaction id
        $POSTransaction = new POSTransactionModel();
        $POSTransaction->amount_paid = $request->input('amount_paid');
        $POSTransaction->created_at = date("Y-m-d h:i");
        $POSTransaction->status_id = 4; // Completed
        $POSTransaction->customer_id = $request->input('customer_id');
        $POSTransaction->customer_name = (string) $request->input('customer_name');
        $POSTransaction->customer_address = (string) $request->input('customer_address');
        $POSTransaction->customer_contact_detail = (string) $request->input('customer_contact_detail');
        $POSTransaction->gcash_name = (string) $request->input('gcash_name');
        $POSTransaction->gcash_num = (string) $request->input('gcash_num');
        $POSTransaction->cc_name = (string) $request->input('cc_name');
        $POSTransaction->cc_num = (string) $request->input('cc_num');
        $POSTransaction->mode_of_payment = (string) $request->input('mode_of_payment');
        $POSTransaction->serial_number = ConfigModel::find(CONFIG_SERIAL_NUMBER)->value;
        $POSTransaction->save();

        ConfigModel::increment_serial_number();

        // insert products
        foreach ($validated['product_id'] as $key => $product_id) {
            $POSTransaction2Product = new POSTransaction2ProductModel();
            $POSTransaction2Product->pos_transaction_id = $POSTransaction->id;
            $POSTransaction2Product->product_id = $product_id;
            $POSTransaction2Product->quantity = $validated['quantity'][$key];

            $senior_discount = $request->input("senior_discounted") == "true" ? (float) ConfigModel::find(3)->value : 0;
            $POSTransaction2Product->price = $validated['price'][$key] * (1 - $senior_discount);
            $Product = Product::find($product_id);
            $POSTransaction2Product->selling_price = $Product->price;
            $POSTransaction2Product->base_price = $Product->base_price;
            $POSTransaction2Product->senior_discount = $senior_discount;

            $POSTransaction2Product->save();

            $pt2p_id = $POSTransaction2Product->id;

            // reduce stocks in inventory
            //find inventory items with similar item_code of product_id
            $item_code = Product::find($product_id)->item_code;
            $deducting_quantity = $validated['quantity'][$key];
            $inventory_items = Inventory::select(DB::raw('i.id as i_id, i.stock, p.id as p_id'))
                ->from('inventory as i')
                ->join('product as p', 'p.id', '=', 'i.product_id')
                ->where('p.item_code', $item_code)
                ->orderBy('p.expiration_date', 'asc');

            // dd($inventory_items->get());

            foreach ($inventory_items->get() as $key => $inventory_item) {
                $previous_quantity = Inventory::getStock($inventory_item->p_id);
                DB::enableQueryLog();
                $DeductInv = new Inventory();
                $DeductInv = $DeductInv->where('id', $inventory_item->i_id);
                // deduct all inventory stock
                if ($deducting_quantity > $inventory_item->stock) {
                    $DeductInv->update(
                        [
                            'stock' => 0,
                        ]
                    );
                    $updated_quantity = Inventory::find($inventory_item->i_id)->stock;
                    $deducting_quantity -= $inventory_item->stock;

                    $this->logInventoryDeduction($inventory_item->i_id, $previous_quantity, $updated_quantity, $pt2p_id);
                    continue;
                }

                // deduct remaining
                $DeductInv->update(
                    [
                        'stock' => DB::raw("stock - $deducting_quantity"),
                    ]
                );
                $updated_quantity = Inventory::find($inventory_item->i_id)->stock;
                $deducting_quantity -= $inventory_item->stock;

                $this->logInventoryDeduction($inventory_item->i_id, $previous_quantity, $updated_quantity, $pt2p_id);
                break;
            }
        }

        $request->session()->flash('msg_success', 'Transaction completed!');
        return redirect(action([POSController::class, 'finish'], ['transaction_id' => $POSTransaction->id]));
    }

    private function logInventoryDeduction($inventory_id, $previous_quantity, $updated_quantity, $pt2p_id)
    {
        // Log
        if ($previous_quantity == 0 && $updated_quantity == 0) {
            return;
        }

        InventoryLog::log(
            $inventory_id,
            $previous_quantity,
            $updated_quantity,
            9, // 9 = stock deducted
            $pt2p_id
        );
    }

    public function finish(Request $request)
    {
        $search = $request->input('q');
        $data['title'] = 'Transactions';
        $data['heading'] = 'Transactions';
        $data['transaction_id'] = $request->input('transaction_id');
        $this->setUserContent($data);

        $data['search'] = $search;

        $Transaction = new POSTransactionModel();
        $data['transactions'] = $Transaction->getPosTransactions($search, URI_POS_TRANSACTIONS);
        $data['d_none'] = count($data['transactions']) ? 'd-none' : '';


        return view('pages.cashier.pos-finish', $data);
    }

    // Async
    public function searchPosTransction(Request $request)
    {
        $search = $request->input('q');

        $Transaction = new POSTransactionModel();

        DB::enableQueryLog();
        $data['transactions'] = $Transaction->getPosTransactions($search, URI_POS_TRANSACTIONS);
        $data['search'] = "$search";
        $rows = (string) view("components.pos-transactions-list", $data);

        $data['d_none'] = count($data['transactions']) ? 'd-none' : '';
        $table_empty = (string) view("layouts.empty-table", $data);
        $links = (string) $data['transactions']->links();
        $row_count = count($data['transactions']);
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

    public function receipt(Request $request)
    {
        $data['transaction_id'] = $request->input('transaction_id');
        $data['title'] = 'JG Ramos Drugmart';
        $data['heading'] = 'Transaction Completed';
        $data['cashier_name'] = Auth::user()->first_name . ' ' . Auth::user()->last_name;
        $data['customer'] = POSTransactionModel::find($request->input('transaction_id'));
        $data['items'] = POSTransactionModel::select(DB::raw('
            pt.created_at, pt.amount_paid, 
            pt.customer_name, pt.customer_address, pt.customer_contact_detail,
            pt2p.id pt2p_id, pt2p.quantity, pt2p.price, pt2p.selling_price, pt2p.senior_discount,
            p.name p_name'))
            ->from('pos_transaction as pt')
            ->join('pos_transaction2product as pt2p', 'pt2p.pos_transaction_id', '=', 'pt.id')
            ->join('product as p', 'p.id', '=', 'pt2p.product_id')
            ->where('pt2p.pos_transaction_id', $data['transaction_id'])
            ->get();

        $view = (string) view('pages.cashier.pos-receipt', $data);
        // return $view;        
        $static_rows_count = 28;
        $row_height = 16;
        $base_receipt_height = $static_rows_count * $row_height;
        $paper_height = $base_receipt_height + $row_height * $this->getItemRows($data['items']);
        $customPaper = array(0, 0, 264, $paper_height);

        $options = new Options();
        $dompdf = new Dompdf();
        $publicPath = base_path('public/');
        $current_options = $dompdf->getOptions();
        $options->set('chroot', $publicPath);
        $options->set('fontDir', $publicPath);

        $dompdf->setPaper($customPaper);
        $dompdf->setOptions($options);
        $dompdf->setBasePath(base_path('public/'));

        $dompdf->loadHTML($view);
        $dompdf->render();
        $dompdf->stream($request->input('transaction_id'));
    }

    private function getItemRows($items)
    {
        $row_count = 0;
        $item_label_one_line_character_count = 25;
        foreach ($items as $item) {
            $item_label = "{$item->p_name} x {$item->quantity}";
            $item_label_character_count = strlen($item_label);
            $row_count += ceil($item_label_character_count / $item_label_one_line_character_count);
        }
        return $row_count;
    }

    public function inventorySearch(Request $request)
    {
        DB::enableQueryLog();
        $product = $this->getProductFromRequest($request);

        $response = [
            'result' => $product,
            'last_query' => DB::getQueryLog(),
        ];

        $response = json_encode($response);
        return Response()->json($response);
    }

    public function getTableRow(Request $request)
    {
        $tbody_content = '';
        $product = $this->getProductFromRequest($request);

        if (!empty($product)) {
            $data['p_id'] = $product->p_id;
            $data['code'] = $product->item_code;
            $data['name'] = $product->p_name;
            $data['description'] = $product->description;
            $data['quantity'] = $request->input('quantity');
            $data['price'] = $product->price;
            $data['subtotal'] = $data['price'] * $data['quantity'];
            $data['form'] = $request->input('form');
            $data['xmark_attr'] = 'data-bs-toggle="modal" data-bs-target="#pin-modal"';
            $tbody_content = (string) view("components.pos-row", $data);
        }

        $response = [
            'result' => $product,
            'tbody' => $tbody_content,
            'table_empty' => (string) view('layouts.empty-table'),
        ];

        $response = json_encode($response);
        return Response()->json($response);
    }

    private function getProductFromRequest($request)
    {
        DB::enableQueryLog();
        $inventory = Inventory::select(
            DB::raw("SUM(i.stock) i_stock,
            (SELECT MIN(product_id) 
                FROM inventory i2
                INNER JOIN product p2
                ON p2.id = i2.product_id
                WHERE p2.item_code = p.item_code
                GROUP BY p2.item_code) p_id, p.item_code, p.name p_name,
        p.description, p.price, p.unit, p.expiration_date, p.stock p_stock,
        c.name c_name
            ")
        )
            ->from('inventory as i')
            ->join('product as p', 'p.id', '=', 'i.product_id')
            ->join('product_category as c', 'c.id', '=', 'p.category_id')
            ->whereNull('i.status_id');

        if ($request->input('item_code')) {
            $inventory = $inventory->where('p.item_code', $request->input('item_code'));
        }

       
        if ($request->input('item_name')) {
            $inventory = $this->setWhereSearch(
                $inventory,
                $request->input('item_name'),
                ['p.name' => '='],
                ['p.name']
            );
            $inventory->where('p.name', '!=', '');
        }
        
        // do not allow empty input to generate all results
        if ($request->input('item_name') == "" && $request->input('item_code') == "") {
            $inventory = $inventory->whereRaw('NULL');
        }

        $inventory->groupBy('p.item_code');
         // echo $inventory->toSql();
        // die();        
        return $inventory->first();
    }

    public function installments(Request $request)
    {
        $search = $request->input('q');

        $data['heading'] = "Installments";
        $data['title'] = "Installments";
        $data['search'] = $search;
        $data['view_action'] = route('pos_installment_details');
        $data['user'] = Role::find(Auth::user()->role_id)->name;

        $Installments = new POSTransactionModel();
        $data['installments'] = $Installments->getPosTransactions($search, URI_POS_INSTALLMENTS, MODE_CREDIT_CARD);
        $data['d_none'] = count($data['installments']) ? 'd-none' : '';

        return view('pages.installments', $data);
    }

    public function installment_details($transaction_id)
    {
        $model = new POSTransaction2ProductModel();
        $data['heading'] = 'Installment Details';
        $data['pos_transaction2products'] = $model->getSalesReportFor($transaction_id);
        $data['transaction_id'] = $transaction_id;
        $data['user'] = Role::find(Auth::user()->role_id)->name;

        return view('pages.installment-details', $data);
    }

    public function searchInstallment(Request $request)
    {
        $search = $request->input('q');

        $Transaction = new POSTransactionModel();

        DB::enableQueryLog();
        $data['installments'] = $Transaction->getPosTransactions($search, URI_POS_INSTALLMENTS, MODE_CREDIT_CARD);
        $data['view_action'] = $request->input('view_action');
        $data['search'] = "$search";
        $rows = (string) view("components.installment-list", $data);

        $data['d_none'] = count($data['installments']) ? 'd-none' : '';
        $table_empty = (string) view("layouts.empty-table", $data);
        $links = (string) $data['installments']->links();
        $row_count = count($data['installments']);
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

    public function payBalance(Request $request)
    {
        $transaction_id = $request->input('transaction_id');
        POSTransactionModel::where('id', $transaction_id)
            ->update(
                ['amount_paid' => DB::raw('amount_paid + ' . $request->input('pay_amount'))]
            );

        $request->session()->flash('msg_success', 'Payment successfull for Transaction #' . $transaction_id);
        return redirect(route('pos_installments'));
    }
}
