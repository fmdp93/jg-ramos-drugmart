<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\SalesReportController;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSTransaction2ProductModel extends Model
{
    use HasFactory;

    protected $table = "pos_transaction2product";

    public $timestamps = false;

    /**
     * SalesReport are grouped pos_transaction2product sales
     */
    public function getSalesReport($from = "", $to = "", $paginated = true)
    {
        $time_start = "00:00:00";
        $time_end = "23:59:59";

        DB::enableQueryLog();
        $model = POSTransaction2ProductModel::select(DB::raw("
            pt2p.pos_transaction_id t_id,             
            pt2p.quantity pt2p_quantity, 
            pt2p.refunded_quantity,
            SUM(pt2p.quantity - pt2p.refunded_quantity) pt2p_quantities,
            SUM(pt2p.price * (pt2p.quantity - pt2p.refunded_quantity) * (1 - pt2p.senior_discount)) pt2p_price_total,
            pt.created_at t_date, pt.amount_paid,
            p.name p_name, p.description
        "))
            ->from("pos_transaction2product as pt2p")
            ->join("pos_transaction as pt", "pt.id", "=", "pt2p.pos_transaction_id")
            ->join("product as p", "p.id", "=", "pt2p.product_id")
            ->where("status_id", 4) //completed
            ->groupBy("pt.id")
            ->having(DB::raw("pt2p_quantity - refunded_quantity"), ">", 0);

        if ($from && $to) {
            $model = $model->where("pt.created_at", ">=", $from . " $time_start")
                ->where("pt.created_at", "<=", $to . " $time_end");
        }

        $model = $model->orderBy('pt.created_at');

        if ($paginated) {
            $model = $model->paginate(Config::get('constant.per_page'))
                ->withPath(action([SalesReportController::class, 'index']))
                ->appends(
                    [
                        'from' => $from,
                        'to' => $to,
                    ]
                )
                ->withQueryString();
        }

        return $model;
    }

    public function getSalesReportFor($id)
    {
        $model = POSTransaction2ProductModel::select(DB::raw("
            pt2p.pos_transaction_id t_id, 
            (pt2p.quantity - pt2p.refunded_quantity) pt2p_quantity,
            pt2p.price pt2p_price,
            pt.created_at t_date, pt.amount_paid,
            p.item_code, p.name p_name, p.description
        "))
            ->from("pos_transaction2product as pt2p")
            ->join("pos_transaction as pt", "pt.id", "=", "pt2p.pos_transaction_id")
            ->join("product as p", "p.id", "=", "pt2p.product_id")
            ->where("status_id", 4) //completed
            ->where("pt.id", $id)
            ->having("pt2p_quantity", ">", 0);
        return $model;
    }

    public function getSalesReportTotals($from = "", $to = "")
    {
        $time_start = "00:00:00";
        $time_end = "23:59:59";
        $model = POSTransaction2ProductModel::select(DB::raw("
            SUM(pt2p.quantity - pt2p.refunded_quantity) total_items, 
            SUM(pt2p.base_price * (pt2p.quantity - pt2p.refunded_quantity)) total_price,
            SUM((pt2p.quantity - pt2p.refunded_quantity) * pt2p.price) total_sales
        "))
            ->from("pos_transaction2product as pt2p")
            ->join("pos_transaction as pt", "pt.id", "=", "pt2p.pos_transaction_id")
            ->join("product as p", "p.id", "=", "pt2p.product_id")
            ->where("status_id", 4); //completed
        if ($from && $to) {
            $model = $model->where("pt.created_at", ">=", $from . " $time_start")
                ->where("pt.created_at", "<=", $to . " $time_end");
        }

        return $model->first();
    }

    public function refundPosTransaction2Product($product, $refund_quantity, $remark)
    {
        // set refund related columns in pos_order2products
        $a_product = POSTransaction2ProductModel::find($product->id);
        // checks and sets the max refundable quantity
        $refundable_quantity = $a_product->quantity - $a_product->refunded_quantity;
        $refund_qty = ($refundable_quantity - $refund_quantity)
            >= 0 ? $refund_quantity : $refundable_quantity;
        $a_product->refunded_quantity += $refund_qty;
        $a_product->remark = $remark;

        $a_product->refunded_at = date("Y-m-d h:i:s");
        $a_product->save();

        return $refund_qty;
    }

    public function getPosTransaction($search, $page_path)
    {
        $model = $this::select(DB::raw('
            pt.id pt_id, pt.created_at, pt.customer_name, pt.amount_paid, 
            pt2p.remark, pt2p.price pt2p_price, pt2p.refunded_quantity,
                pt2p.refunded_at,
            p.name p_name           
            '))
            ->from('pos_transaction as pt')
            ->join('pos_transaction2product as pt2p', 'pt2p.pos_transaction_id', '=', 'pt.id')
            ->join('product as p', 'p.id', '=', 'pt2p.product_id')
            ->orWhere(function ($query) use ($search) {
                $query->where('pt.id', $search)
                    ->orWhere('customer_name', 'LIKE', "%$search%")
                    ->orWhere('created_at', 'LIKE', "%$search%");
            })
            ->whereNotNull('refunded_at')
            ->orderBy('pt.id', 'desc')
            ->paginate(Config::get('constant.per_page'))
            ->withPath($page_path)
            ->appends(
                [
                    'q' => $search,
                ]
            );

        return $model;
    }
}
