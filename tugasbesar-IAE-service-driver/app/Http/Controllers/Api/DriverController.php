<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Display a listing of all drivers
     */
    public function index()
    {
        $drivers = Driver::with('assignments')->get();
        
        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    /**
     * Store a newly created driver
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'status' => 'nullable|in:available,busy,offline'
        ]);

        $driver = Driver::create([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'status' => $validated['status'] ?? 'available'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Driver created successfully',
            'data' => $driver
        ], 201);
    }

    /**
     * Display the specified driver
     */
    public function show($id)
    {
        $driver = Driver::with('assignments')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $driver
        ]);
    }

    /**
     * Update the specified driver
     */
    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:available,busy,offline'
        ]);

        $driver->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Driver updated successfully',
            'data' => $driver
        ]);
    }

    /**
     * Remove the specified driver
     */
    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver deleted successfully'
        ]);
    }

    /**
     * Get available drivers only
     */
    public function available()
    {
        $drivers = Driver::where('status', 'available')->get();
        
        return response()->json([
            'success' => true,
            'data' => $drivers,
            'count' => $drivers->count()
        ]);
    }
}
