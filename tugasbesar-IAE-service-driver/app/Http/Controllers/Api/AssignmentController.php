<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\DriverAssignment;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    /**
     * Jalur 1: REST API (Tetap dipertahankan biar aman)
     */
    public function store(Request $request)
    {
        // 1. Validasi request
        $request->validate([
            'order_id' => 'required|string',
        ]);

        // 2. Cari satu driver yang statusnya masih 'available'
        $driver = Driver::where('status', 'available')->first();

        // 3. Jika semua driver sedang sibuk/tidak ada yang tersedia
        if (!$driver) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Semua driver sedang sibuk. Gagal mengalokasikan kurir.'
            ], 422);
        }

        // 4. Jika driver tersedia, buat data penugasan baru di tabel driver_assignments
        $assignment = DriverAssignment::create([
            'order_id' => $request->order_id,
            'driver_id' => $driver->id,
            'status' => 'assigned'
        ]);

        // 5. Update status driver tersebut menjadi 'busy' agar tidak menerima orderan lain
        $driver->update([
            'status' => 'busy'
        ]);

        // 6. Simulasi Pengiriman Notifikasi (Dicatat ke log sistem internal Laravel)
        Log::info("NOTIFIKASI: Driver {$driver->name} (ID: {$driver->id}) ditugaskan otomatis untuk Order ID: {$request->order_id}");

        // 7. Kembalikan respon sukses berupa JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Driver berhasil ditugaskan secara otomatis!',
            'data' => [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'assigned_driver' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone_number' => $driver->phone_number,
                    'current_status' => $driver->status
                ]
            ]
        ], 201);
    }

    /**
     * FUNGSI BARU: Update Status Penugasan (REST API)
     * Menangani request PATCH /api/assignments/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        // 1. Validasi Input status agar sinkron dengan Enum database PostgreSQL kelompokmu
        $request->validate([
            'status' => 'required|in:assigned,ongoing,completed',
        ]);

        // 2. Cari data assignment berdasarkan ID
        $assignment = DriverAssignment::find($id);

        if (!$assignment) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Data assignment tidak ditemukan.'
            ], 404);
        }

        // 3. Update status assignment
        $assignment->update([
            'status' => $request->status
        ]);

        // 4. JIKA status selesai (completed), otomatis kembalikan status Driver menjadi 'available' lagi
        if ($request->status === 'completed') {
            $driver = Driver::find($assignment->driver_id);
            if ($driver) {
                $driver->update(['status' => 'available']);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status assignment berhasil diperbarui!',
            'data' => $assignment
        ], 200);
    }

    /**
     * Jalur 2: GRAPHQL INTEGRATION (Ditambahkan untuk jembatan integrasi kelompok)
     */
    public function ping()
    {
        return "Pong! Service Driver Assignment Active.";
    }

    public function storeGraphQL($_, array $args)
    {
        // 1. Tangkap order_id dari parameter GraphQL
        $orderId = $args['order_id'];

        // 2. Cari satu driver yang statusnya masih 'available'
        $driver = Driver::where('status', 'available')->first();

        // 3. Jika semua driver sedang sibuk
        if (!$driver) {
            return [
                'status' => 'failed',
                'message' => 'Semua driver sedang sibuk. Gagal mengalokasikan kurir.',
                'data' => null
            ];
        }

        // 4. Buat data penugasan baru di tabel driver_assignments
        $assignment = DriverAssignment::create([
            'order_id' => $orderId,
            'driver_id' => $driver->id,
            'status' => 'assigned'
        ]);

        // 5. Update status driver tersebut menjadi 'busy'
        $driver->update([
            'status' => 'busy'
        ]);

        // 6. Simulasi log internal Laravel
        Log::info("NOTIFIKASI (GRAPHQL): Driver {$driver->name} (ID: {$driver->id}) ditugaskan otomatis untuk Order ID: {$orderId}");

        // 7. Kembalikan respon sesuai format schema.graphql
        return [
            'status' => 'success',
            'message' => 'Driver berhasil ditugaskan secara otomatis lewat GraphQL!',
            'data' => [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'assigned_driver' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone_number' => $driver->phone_number,
                ]
            ]
        ];
    }
}