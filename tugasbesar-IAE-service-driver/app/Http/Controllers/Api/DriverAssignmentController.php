<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverAssignment;
use Illuminate\Http\Request;

class DriverAssignmentController extends Controller
{
    /**
     * Display all driver assignments
     */
    public function index()
    {
        $assignments = DriverAssignment::with('driver')->get();
        
        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get assignment by tracking number (order_id)
     */
    public function getByTrackingNumber($trackingNumber)
    {
        $assignment = DriverAssignment::with('driver')
            ->where('order_id', $trackingNumber)
            ->first();
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $assignment
        ]);
    }

    /**
     * Update assignment status
     */
    public function updateStatus(Request $request, $id)
    {
        $assignment = DriverAssignment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:assigned,in_transit,delivered,cancelled'
        ]);

        $assignment->update($validated);

        // If delivered or cancelled, set driver back to available
        if (in_array($validated['status'], ['delivered', 'cancelled'])) {
            $assignment->driver->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment status updated',
            'data' => $assignment->load('driver')
        ]);
    }

    /**
     * Get all assignments for a specific driver
     */
    public function getByDriver($driverId)
    {
        $assignments = DriverAssignment::with('driver')
            ->where('driver_id', $driverId)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $assignments,
            'count' => $assignments->count()
        ]);
    }
}
