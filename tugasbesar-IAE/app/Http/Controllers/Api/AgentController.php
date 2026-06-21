<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
// --- TAMBAHKAN INI ---
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AgentController extends Controller
{
    public function index()
    {
        return Agent::with('user')->get();
    }

    public function store(Request $request)
    {
        // Validasi tetap menerima 'full_name' dari Postman biar seragam
        $data = $request->validate([
            'full_name' => 'required',
            'phone' => 'required',
            'branch_name' => 'required',
            'address' => 'required'
        ]);

        // Dipetakan saat create: input 'full_name' dimasukkan ke kolom 'name' di database
        $agent = Agent::create([
            'user_id' => $request->user()->id,
            'name' => $data['full_name'], // <-- PERBAIKAN DI SINI (Disesuaikan ke kolom database)
            'phone' => $data['phone'],
            'branch_name' => $data['branch_name'],
            'address' => $data['address']
        ]);

        // --- SUNTIKAN KODE RABBITMQ ---
        try {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();

            $channel->queue_declare('agent_created_queue', false, true, false, false);

            $msgData = json_encode([
                'agent_id' => $agent->id,
                'full_name' => $agent->name, // Mengambil properti name yang baru diset
                'branch' => $agent->branch_name
            ]);

            $msg = new AMQPMessage($msgData);
            $channel->basic_publish($msg, '', 'agent_created_queue');

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            \Log::error('Gagal kirim pesan ke RabbitMQ: ' . $e->getMessage());
        }
        // --- SELESAI SUNTIKAN ---

        return response()->json($agent);
    }

    public function show($id)
    {
        return Agent::with('user')->findOrFail($id);
    }
}