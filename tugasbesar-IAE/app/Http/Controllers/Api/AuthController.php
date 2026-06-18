<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
// --- TAMBAHKAN INI ---
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required|in:sender,agent,admin',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        // --- SUNTIKAN KODE RABBITMQ ---
        try {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('user_registered_queue', false, true, false, false);

            $msgData = json_encode([
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $user->role
            ]);

            $msg = new AMQPMessage($msgData);
            $channel->basic_publish($msg, '', 'user_registered_queue');

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            \Log::error('Gagal kirim pesan ke RabbitMQ: ' . $e->getMessage());
        }
        // --- SELESAI SUNTIKAN ---

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Register success',
            'token'   => $token,
            'user'    => $user
        ], 201);
    }

    // ... (fungsi lainnya tetap sama)
}
