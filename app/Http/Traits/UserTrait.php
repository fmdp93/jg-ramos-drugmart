<?php

namespace App\Http\Traits;

use App\Models\User;
use App\Models\Action;
use App\Models\AccountLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdateAccountRequest;

trait UserTrait
{
    private function validateLogin(Request $Request, $error_message)
    {
        $session = $Request->session();
        $username = $Request->input('username');
        $password = $Request->input('password');
        // $User = User::
        //     where('username', $username)
        //     ->where('password', $password);
        $User = User::select(DB::raw('r.name as r_name, u.id u_id'))
            ->from('user as u')
            ->join('role as r', 'r.id', '=', 'u.role_id')
            ->where('username', $username)
            ->where('password', $password);

        // validate result from db             
        if (!$user = $User->first()) {
            $Request->session()->flash('error_login', $error_message);
            return;
        }

        $session->put('id', $user->u_id);
        $session->put('username', $username);
        $session->put('password', $password);
        $session->put('role', $User->first()->r_name);

        return;
    }

    public function editAccount()
    {
        $data['heading'] = "EDIT ACCOUNT";
        $data['title'] = "Edit Account";
        $data['user'] = User::find(Auth::id());
        $data['action_class'] = self::$action_class;
        $data['user_content'] = self::$user_content;
        $data['include_content'] = self::$include_content;

        return view('pages.edit-account', $data);
    }

    public function editAccountSave(UpdateAccountRequest $request)
    {
        $validated = $request->validated();

        $updates = [
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'address' => $validated['address'],
            'age' => $validated['age'],
            'contact_num' => $validated['contact_num'],
        ];

        if ($request->input('new_pw')) {
            $updates['password'] = Hash::make($request->input('new_pw'));
        }

        User::where('id', Auth::id())
            ->update($updates);

        $action_id = Action::getEditAccountActionId(Auth::user()->role_id);
        AccountLog::log(Auth::id(), $action_id);

        $request->session()->flash('msg_success', 'Account updated!');
        return redirect()->action([self::$action_class, 'editAccount']);
    }

    private function setUserContent(&$data)
    {        
        if (Auth::user()->role_id == 1) {
            $data['user'] = "admin";
        } else if (Auth::user()->role_id == 2) {
            $data['user'] = 'cashier';
        }
    }
}
