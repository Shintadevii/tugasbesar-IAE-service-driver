<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return Customer::with('user')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required',
            'phone' => 'required',
            'address' => 'required'
        ]);

        $customer = Customer::create([
            'user_id' => $request->user()->id,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'address' => $data['address']
        ]);

        return response()->json($customer);
    }

    public function show($id)
    {
        return Customer::with('user')->findOrFail($id);
    }

    public function search(Request $request)
    {
        return Customer::whereHas('user', function ($q) use ($request) {
            $q->where('email', $request->email);
        })->first();
    }
}