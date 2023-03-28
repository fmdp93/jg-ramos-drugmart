<?php

use App\Models\User;
use App\Http\Middleware\LoginCheck;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RRController;
use App\Http\Middleware\PinMiddleware;
use App\Http\Controllers\PINController;
use App\Http\Controllers\POSController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\PageUnauthorized;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\InventoryMiddleware;
use App\Http\Middleware\PosFinishMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\Test\CarsController;
use App\Http\Controllers\LogManagerController;
use App\Http\Controllers\Test\LoginController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\CashierAccountsController;
use App\Http\Controllers\CashierProductsController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Middleware\AfterPosFinishMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', [IndexController::class, 'index']);
Route::post('/login', [UserController::class, 'login']);

Route::group(['middleware' => 'auth'], function () {
  Route::post('/', [IndexController::class, 'index']);

  /**
   * Admin routes
   */
  Route::get('/unauthorized/admin', [PageUnauthorized::class, 'admin']);
  // Route::group(['middleware' => 'can:do_admin,' . User::class], function () {
  Route::group(['middleware' => RoleMiddleware::class . ':admin'], function () {
    Route::get('/products', [ProductsController::class, 'index']);
    Route::get('/product/{id}', [ProductsController::class, 'getProduct'])
      ->where('id', PATTERN_ID);
    Route::get('/products/print-barcode/', [ProductsController::class, 'printBarcode']);
    Route::get('/products/add-product', [ProductsController::class, 'addProduct']);
    Route::get('/product/get-item-code', [ProductsController::class, 'getNewItemCode']);

    Route::post('/product/add', [ProductsController::class, 'store']);
    Route::post('/product/update', [ProductsController::class, 'updateProduct']);
    Route::delete('/product/delete', [ProductsController::class, 'delete']);

    Route::get('/product/search', [ProductsController::class, 'search']);
    Route::get('/products/category/add', [CategoryController::class, 'index']);
    Route::post('/category/add', [CategoryController::class, 'store']);
    Route::delete('/category/delete', [CategoryController::class, 'delete']);


    //Async
    Route::get('/product/get-item-code-details', [ProductsController::class, 'getItemCodeDetails']);

    Route::get('/inventory', [InventoryController::class, 'index'])
      ->middleware(InventoryMiddleware::class);
    Route::post('/inventory/archive', [InventoryController::class, 'archive'])
      ->name('inventory_archive');
    Route::get('/inventory/archives', [InventoryController::class, 'archives'])
      ->name('inventory_archives');
    Route::post('/inventory/unarchive', [InventoryController::class, 'unarchive'])
      ->name('inventory_unarchive');

    // Async
    Route::get('/inventory/archive-search', [InventoryController::class, 'archiveSearch'])
      ->name('inventory_archive_search');

    Route::get('/inventory/orders', [InventoryController::class, 'orders'])
      ->name('orders');

    Route::get('/inventory/purchase-order', [InventoryController::class, 'purchaseOrder']);
    Route::get('/inventory/suppliers', [SuppliersController::class, 'index'])
      ->name('suppliers');
    Route::get('/inventory/suppliers/search', [SuppliersController::class, 'searchSupplier'])
      ->name('search_supplier');

    Route::get('/inventory/add-supplier', [SuppliersController::class, 'add_supplier'])
      ->name('add_supplier');
    Route::post('/inventory/add-supplier/submit', [SuppliersController::class, 'add_supplier_submit'])
      ->name('add_supplier_submit');
    Route::get('/inventory/edit-supplier', [SuppliersController::class, 'edit_supplier'])
      ->name('edit_supplier');
    Route::post('/inventory/edit-supplier/submit', [SuppliersController::class, 'edit_supplier_submit'])
      ->name('edit_supplier_submit');
    Route::delete('/inventory/delete-supplier', [SuppliersController::class, 'delete_supplier'])
      ->name('delete_supplier');

    Route::post('/inventory/order-received', [InventoryController::class, 'orderReceived']);
    Route::post('/inventory/purchase-order-cancel', [InventoryController::class, 'orderCancel'])
      ->name('purchase_order_cancel');
    Route::get('/inventory/get-inventory-order-processing', [InventoryController::class, 'getInventoryOrderProcessing']);
    Route::get('/inventory/order-products', [InventoryController::class, 'orderProducts']);
    Route::post('/inventory/add', [InventoryController::class, 'store']);
    defined('URI_INV_ORDER_HISTORY') || define('URI_INV_ORDER_HISTORY', '/inventory/order-history');
    Route::get(URI_INV_ORDER_HISTORY, [InventoryController::class, 'orderHistory'])
      ->name('inventory_order_history');
    Route::get('/inventory/back-order', [InventoryController::class, 'backOrder'])
      ->name('back_order');

    Route::get('/inventory/order-history/print', [InventoryController::class, 'print_inventory_order_report'])
      ->name('print_inventory_order_report');
    // Async 
    Route::get('/inventory/search', [InventoryController::class, 'inventorySearch']);
    Route::get('/inventory/order-history-search', [InventoryController::class, 'searchOrderHistory'])
      ->name('search_order_history');
    Route::get('/inventory/search_back_order', [InventoryController::class, 'searchBackOrder'])
      ->name('search_back_order');
    Route::get('/purchase-order/search/', [InventoryController::class, 'purchaseOrderSearch']);
    Route::get('/purchase-order/supplier-search/', [InventoryController::class, 'purchaseOrderSupplierSearch']);
    Route::get('/vendor/search', [SuppliersController::class, 'searchVendor']);
    Route::get('/product/add-item-search-vendor', [SuppliersController::class, 'addItemSearchVendor']);


    Route::get('/report', [SalesReportController::class, 'index'])
      ->name('sales_report');
    Route::get('/report/print', [SalesReportController::class, 'print_sales_report'])
      ->name('print_sales_report');
    Route::get('/report/transaction/{id}', [SalesReportController::class, 'posTransaction2Product'])
      ->name('pos_transaction2product')
      ->where('id', PATTERN_ID);
    defined('URI_INVENTORY_REPORT') || define('URI_INVENTORY_REPORT', '/report/inventory/');
    Route::get(URI_INVENTORY_REPORT, [InventoryReportController::class, 'index'])
      ->name('inventory_report');

    Route::get('/report/inventory/print', [InventoryReportController::class, 'print_inventory_report'])
      ->name('print_inventory_report');
    Route::get('/report/inventory/details/{id}', [InventoryReportController::class, 'details'])
      ->name('inventory_report_details')
      ->where('id', PATTERN_ID);
    Route::get('/report/inventory/details/print/{id}', [InventoryReportController::class, 'print_details'])
      ->name('print_inventory_report_details')
      ->where('id', PATTERN_ID);
    Route::get('/report/inventory/{id}', [InventoryReportController::class, 'printReturnRefund'])
      ->name('print_return_refund')
      ->where('id', PATTERN_ID);


    Route::get('/accounts', [AccountsController::class, 'index']);
    Route::get('/accounts/add-cashier', [AccountsController::class, 'addCashier']);
    Route::post('/accounts/add-cashier/add', [AccountsController::class, 'addCashierSubmit']);
    Route::get('/accounts/edit-account', [AccountsController::class, 'editAccount']);
    Route::post('/accounts/edit-account/save', [AccountsController::class, 'editAccountSave']);
    Route::delete('/accounts/delete-cashier', [AccountsController::class, 'deleteCashier']);

    Route::get('/set-pin', [PINController::class, 'setPinFlash']);

    $pin_mw = PinMiddleware::class;
    $action = action([PINController::class, 'setPinFlash']);
    Route::group(['middleware' => "$pin_mw:$action"], function () {
      Route::get('/log-manager', [LogManagerController::class, 'index']);
      Route::get('/log-manager/product', [LogManagerController::class, 'product']);
      Route::get('/log-manager/inventory', [LogManagerController::class, 'inventory']);
      Route::get('/log-manager/account', [LogManagerController::class, 'account']);
      Route::get('/log-manager/login', [LogManagerController::class, 'login']);

      Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
      Route::get('/settings/backup-db', [SettingsController::class, 'backupDb'])->name('backup_db');
      Route::post('/settings/restore-db', [SettingsController::class, 'restoreDb'])->name('restore_db');
      Route::post('/settings/update_serial_number', [SettingsController::class, 'update_serial_number'])
        ->name('update_serial_number');
    });
    Route::get('/log-manager/check-pin', [LogManagerController::class, 'checkPin']);



    Route::get('/switch-user', [UserController::class, 'index']);
    Route::get('/pos/search_pos_transaction', [POSController::class, 'searchPosTransction'])
      ->name('search_pos_transaction');
  });

  /**
   * Cashier Routes
   */
  Route::get('/unauthorized/cashier', [PageUnauthorized::class, 'cashier']);
  // Route::group(['middleware' => 'can:do_cashier,' . User::class], function () {
  Route::group(['middleware' => RoleMiddleware::class . ':cashier'], function () {

    // Cashier/products
    Route::get('/cashier-products', [CashierProductsController::class, 'index']);
    // Async Requests
    Route::get('/cashier/products/search', [CashierProductsController::class, 'search']);

    Route::get('/cashier-settings', [CashierAccountsController::class, 'editAccount']);
    Route::post('/cashier-settings/update', [CashierAccountsController::class, 'editAccountSave']);

    Route::get('/customer', [CustomerController::class, 'index'])
      ->name('customer');
    Route::get('/customer/add', [CustomerController::class, 'add_customer'])
      ->name('add_customer');
    Route::post('/customer/add_submit', [CustomerController::class, 'add_customer_submit'])
      ->name('add_customer_submit');
    Route::get('/customer/edit', [CustomerController::class, 'edit_customer'])
      ->name('edit_customer');
    Route::post('/customer/edit_submit', [CustomerController::class, 'edit_customer_submit'])
      ->name('edit_customer_submit');
    Route::delete('/customer/delete', [CustomerController::class, 'delete_customer'])
      ->name('delete_customer');
    // Asnyc
    Route::get('/customer/search-for-table', [CustomerController::class, 'searchForTable'])
      ->name('customer_search_for_table');
  });

  /** POS Routes */
  Route::get('/pos', [POSController::class, 'index']);
  Route::get('/pos/inventory-search', [POSController::class, 'inventorySearch']);
  Route::get('/pos/get-table-row', [POSController::class, 'getTableRow']);
  Route::post('/pos/checkout', [POSController::class, 'checkout']);

  defined('URI_POS_TRANSACTIONS') || define('URI_POS_TRANSACTIONS', '/pos/transactions');
  Route::get(URI_POS_TRANSACTIONS, [POSController::class, 'finish'])
    ->name('pos_finish');

  Route::get('/pos/receipt', [POSController::class, 'receipt'])
    ->name('receipt');
  Route::get('/pos/receipt-url', [POSController::class, 'receiptUrl']);

  defined('URI_POS_INSTALLMENTS') || define('URI_POS_INSTALLMENTS', '/pos/installments');
  Route::get(URI_POS_INSTALLMENTS, [POSController::class, 'installments'])
    ->name('pos_installments');
  Route::get('/pos/installment-details/{id?}', [POSController::class, 'installment_details'])
    ->name('pos_installment_details')
    ->where('id', PATTERN_ID);

  Route::post('/pos/pay_balance/', [POSController::class, 'payBalance'])
    ->name('pay_balance');

  // Async
  Route::get('/customer/search-for-pos', [CustomerController::class, 'searchForPos'])
    ->name('customer_search_for_pos');
  Route::get('/pos/search_installment', [POSController::class, 'searchInstallment'])
    ->name('search_installment');

  // Return Refunds routes
  defined('RR_INDEX') || define('RR_INDEX', '/pos/return-refund');
  Route::get(RR_INDEX, [RRController::class, 'index'])
    ->name('rr_index');
  Route::get('search_rr_pt_id', [RRController::class, 'searchRR'])
    ->name('search_rr_pt_id');
  Route::get('/pos/return-refund/{pt_id?}', [RRController::class, 'rr_transaction_details'])
    ->name('rr_transaction_details')
    ->where('pt_id', '[0-9]');


  Route::get('/rr/inventory-search', [RRController::class, 'inventorySearch']);
  Route::get('/rr/get-table-row', [RRController::class, 'getTableRow']);

  Route::get('/pos/set-pin', [PINController::class, 'setPinFlash']);
  $pin_mw = PinMiddleware::class;
  $action = action([PINController::class, 'setPinFlash']);
  Route::group(['middleware' => "$pin_mw:$action"], function () {
    Route::get('/pos/return-refund/form', [RRController::class, 'rrform'])
      ->name('rr_form');
    Route::post('/pos/return-refund-submit', [RRController::class, 'store']);
  });

  /** End of POS routes */

  Route::get('/pin/validate-pin', [PINController::class, 'validatePin']);
  Route::post('/pin/submit-pin', [PINController::class, 'submitPin']);
  // Route::get('/settings', [SettingsController::class, 'index']);
  Route::get('/logout', [UserController::class, 'logout']);
});



// Route::get('/login/about', [LoginController::class, 'about']);
// Route::get('/login/{id}', [LoginController::class, 'user'])
//   ->where('id', '\d+');

// // Multiple parameter filtered by pattern
// Route::get('/product/{name}/{brand_id}', [TestController::class, 'product'])
//   ->where([
//     'name' => '[a-zA-Z]+',
//     'brand_id' => '\d+',
//   ])->name('show_product');