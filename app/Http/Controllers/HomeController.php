<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Auth;
use session;

class HomeController extends Controller
{ 
    public function index()
    {
        $user = Auth::user(); // Get the authenticated user
        
        if ($user) {

            session()->flash('alert-success', 'Login Successfully');
            return redirect('/Admin/dashboard');
        } else {
            Auth::guard('web')->logout();
            return redirect('/');
        }
    }
}
