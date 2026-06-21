<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
// --- TAMBAHKAN INI ---
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class CustomerController extends Controller
{
    public function index()
    {
        return Customer::with('user')->get();
    }

    public function store(Request $request)
    {
        // Validasi tetap menerima 'full_name' dari Postman
        $data = $request->validate([
            'full_name' => 'required',
            'phone' => 'required',
            'address' => 'required'
        ]);

        // Dipetakan saat create: input 'full_name' dimasukkan ke kolom 'name' di database
        $customer = Customer::create([
            'user_id' => $request->user()->id,
            'name' => $data['full_name'], // <-- PERBAIKAN DI SINI
            'phone' => $data['phone'],
            'address' => $data['address']
        ]);

        // --- SUNTIKAN KODE RABBITMQ ---
        try {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();

            $channel->queue_declare('customer_created_queue', false, true, false, false);

            $msgData = json_encode([
                'customer_id' => $customer->id,
                'full_name' => $customer->name, // Mengambil properti name yang baru diset
                'phone' => $customer->phone
            ]);

            $msg = new AMQPMessage($msgData);
            $channel->basic_publish($msg, '', 'customer_created_queue');

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            \Log::error('Gagal kirim pesan ke RabbitMQ: ' . $e->getMessage());
        }
        // --- SELESAI SUNTIKAN ---

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
        })->first(); // <-- Tanda kurung kurawal penutup sudah diperbaiki di sini
    }
}