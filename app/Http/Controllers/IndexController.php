<?php

namespace App\Http\Controllers;

use stdClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function index(Request $Request)
    {        
        $role_id = Auth::user()->role_id ?? null;
        if($role_id == 1){ //admin
            return redirect(action([ProductsController::class, 'index']));
        }else if($role_id == 2){ // cashier
            return redirect(action([POSController::class, 'index']));
        }
        $data['h1'] = "JG Ramos Drugmart Login";

        return view('index', $data);
    }

    public function about()
    {
        return view('about');
    }
}
