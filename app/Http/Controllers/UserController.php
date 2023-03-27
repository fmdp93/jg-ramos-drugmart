<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\UserTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Rules\UserIsActive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use UserTrait;

    public function login(Request $request)
    {
        // $this->validateLogin($Request, "Invalid username/password");
        $username = $request->input('username');
        $password = $request->input('password');
        $request->session()->flash('msg_error', 'Wrong Username/Password!!');
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
            'deleted_at' => [new UserIsActive],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            LoginLog::log(Auth::id(), 14); // 14 is log in

            switch (Auth::user()->role_id) {
                case 1: //admin
                    return redirect()->intended(action([InventoryController::class, 'index']));
                    break;
                case 2: //cashier
                    return redirect()->intended(action([CashierProductsController::class, 'index']));
                    break;
            }
        }

        return back();
    }

    public function logout(Request $request)
    {
        $user_id = Auth::id();
        Auth::logout();

        LoginLog::log($user_id, 15); // 15 is log out

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
