<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
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
        $data = $request->validate([
            'full_name' => 'required',
            'phone' => 'required',
            'branch_name' => 'required',
            'address' => 'required'
        ]);

        $agent = Agent::create([
            'user_id' => $request->user()->id,
            'full_name' => $data['full_name'],
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
                'full_name' => $agent->full_name,
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
