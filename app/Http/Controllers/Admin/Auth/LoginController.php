<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        return view('Admin.Pages.Auth.Login');
    }

    public function loginCheck(Request $request)
    {
        $message = [
            'email.required'    => 'Please Enter Valid Email Id.',
            'password.required' => 'Please Enter Valid Password.',
            'password.min'      => 'Please Enter Minimum 8 digits Password.',
        ];

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ], $message);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            $intendedUrl = session('intended_url', route('dashboard'));
            session()->forget('intended_url');

            return response()->json([
                'success' => 'Login Successful. Redirecting....',
                'redirect_url' => $intendedUrl
            ], 200);
        }

        return response()->json(['errors' => 'Please Enter Valid email or password.'], 400);
    }

    public function Logout()
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login')->with('success', 'Logout Successfully!');
    }
}
