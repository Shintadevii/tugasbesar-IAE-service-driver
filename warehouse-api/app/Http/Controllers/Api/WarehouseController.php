<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Get all warehouse orders
     */
    public function orders()
    {
        $orders = Order::with('product')->get();
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get all products in warehouse
     */
    public function products()
    {
        $products = Product::with('stocks')->get();
        
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get all stock information
     */
    public function stocks()
    {
        $stocks = Stock::with('product')->get();
        
        return response()->json([
            'success' => true,
            'data' => $stocks
        ]);
    }

    /**
     * Get low stock alerts
     */
    public function lowStock()
    {
        $lowStocks = Stock::with('product')
            ->whereRaw('quantity <= reorder_level')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $lowStocks,
            'count' => $lowStocks->count()
        ]);
    }

    /**
     * Update order status in warehouse
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:received,processing,dispatched,completed'
        ]);
        
        $order->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'data' => $order->load('product')
        ]);
    }

    /**
     * Dispatch order from warehouse (reduce stock)
     */
    public function dispatch($trackingNumber)
    {
        $order = Order::where('reference', $trackingNumber)->firstOrFail();
        
        if ($order->status !== 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not in received status'
            ], 400);
        }
        
        // Update order status
        $order->update(['status' => 'dispatched']);
        
        // Reduce stock
        $stock = Stock::where('product_id', $order->product_id)
            ->where('location', 'Main Warehouse')
            ->first();
        
        if ($stock && $stock->quantity > 0) {
            $stock->decrement('quantity', $order->quantity);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Order dispatched from warehouse',
            'data' => $order->load('product')
        ]);
    }
}
