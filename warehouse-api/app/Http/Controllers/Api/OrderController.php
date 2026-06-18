<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        return response()->json(Order::with('product')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($data['product_id']);

        $order = Order::create([
            'reference'  => 'ORD-' . strtoupper(Str::random(8)),
            'status'     => 'pending',
            'product_id' => $product->id,
            'quantity'   => $data['quantity'],
            'total'      => $product->price * $data['quantity'],
        ]);

        return response()->json($order->load('product'), 201);
    }

    public function show(Order $order)
    {
        return response()->json($order->load('product'));
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled',
        ]);

        $order->update($data);

        return response()->json($order);
    }
}