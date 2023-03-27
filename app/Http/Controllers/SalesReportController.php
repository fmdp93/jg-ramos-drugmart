<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use App\Http\Traits\SearchTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\POSTransaction2ProductModel;

class SalesReportController extends Controller
{
    use SearchTrait;
    public function index(Request $request)
    {
        $from = urldecode($request->input('from'));
        $to = urldecode($request->input('to'));
        $model = new POSTransaction2ProductModel();
        $data['heading'] = 'SALES REPORT';
        DB::enableQueryLog();
        $this->dateFilterSetFrom($from);
        $this->dateFilterSetTo($to);
        $data['transactions'] = $model->getSalesReport($from, $to);
        // dd(DB::getQueryLog());
        $total_items = $model->getSalesReportTotals($from, $to);

        $data['total_items'] = $total_items->total_items;
        $data['total_sales'] = $total_items->total_sales;
        $data['total_price'] = $total_items->total_price;
        $data['profit'] = $total_items->profit;
        $data['from'] = $from ? date("F j, Y", strtotime($from)) : 'start';
        $data['to'] = $to ? date("F j, Y", strtotime($to)) : 'end';

        return view('pages.admin.sales-report', $data);
    }    

    public function posTransaction2Product($transaction_id){        
        $model = new POSTransaction2ProductModel();
        $data['heading'] = 'Sales Report Details';
        $data['pos_transaction2products'] = $model->getSalesReportFor($transaction_id);
        $data['transaction_id'] = $transaction_id;

        return view('pages.admin.sales-report-details', $data);
    }

    public function print_sales_report(Request $request){        
        $from = urldecode($request->input('from'));
        $to = urldecode($request->input('to'));
        $model = new POSTransaction2ProductModel();
        $data['heading'] = 'Sales Report';
        DB::enableQueryLog();
        $this->dateFilterSetFrom($from);
        $this->dateFilterSetTo($to);
        $data['transactions'] = $model->getSalesReport($from, $to, false)->get();
        // dd(DB::getQueryLog());
        $total_items = $model->getSalesReportTotals($from, $to);

        $data['total_items'] = $total_items->total_items;
        $data['total_sales'] = $total_items->total_sales;
        $data['total_price'] = $total_items->total_price;
        $data['profit'] = $total_items->profit;
        $data['from'] = $from ? date("F j, Y", strtotime($from)) : 'start';
        $data['to'] = $to ? date("F j, Y", strtotime($to)) : 'end';

        $view = (string)  view('pages.admin.print-sales-report', $data);        
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
        $dompdf->stream("{$date_range}sales-report");
    }

}
