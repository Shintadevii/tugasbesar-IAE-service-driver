<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Product $product)
    {
        return response()->json($product->stocks);
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'location'      => 'required|string',
            'quantity'      => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
        ]);

        $stock = $product->stocks()->create($data);

        return response()->json($stock, 201);
    }

    public function update(Request $request, Product $product, Stock $stock)
    {
        $data = $request->validate([
            'location'      => 'sometimes|string',
            'quantity'      => 'sometimes|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
        ]);

        $stock->update($data);

        return response()->json($stock);
    }
}