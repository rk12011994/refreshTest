<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Customers;

class CustomerController extends Controller
{
    public function add(Request $request) {
        $input = $request->all();
        $input['full_name'] = $input['first_name'].' '.$input['last_name'];
        $input['QuickBooksID'] = 0;
        $save = Customers::create($input);
        session(['customer_id' => $save->id]);
        if($save) {
            return redirect()->route('quicks');
        }
    }
    public function addCustomer() {
        return view('addCustomer');
    }
}
