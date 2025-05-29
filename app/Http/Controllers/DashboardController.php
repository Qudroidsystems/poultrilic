<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:dashboard', ['only' => ['index']]);
    
    }

    
    public function index(){
        
        //page title
        $pagetitle = "Role Management";


        return view('dashboards.dashboard')->with('pagetitle',$pagetitle);

    }
}
