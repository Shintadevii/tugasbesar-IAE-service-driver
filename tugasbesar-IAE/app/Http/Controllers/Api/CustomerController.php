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

        // --- SUNTIKAN KODE RABBITMQ ---
        try {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();

            $channel->queue_declare('customer_created_queue', false, true, false, false);

            $msgData = json_encode([
                'customer_id' => $customer->id,
                'full_name' => $customer->full_name,
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
        })->first();
    }
}
