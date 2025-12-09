<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('Admin.Pages.UserManagement.index');
    }

    public function fetchUsers()
    {
        $users = User::select([
                'id', 
                'first_name', 
                'last_name', 
                'email', 
                'phone_no', 
                'company_name', 
                'city', 
                'state', 
                'zip_code'
            ])
            ->orderBy('id', 'DESC')
            ->get();

        // Process users for display
        $users->transform(function ($user) {
            // Combine first and last name
            $user->name = $user->first_name . ' ' . $user->last_name;
            
            // Format address
            $user->address = $user->city . ', ' . $user->state . ' ' . $user->zip_code;
            
            return $user;
        });

        return response()->json([
            'data' => $users
        ]);
    }
}