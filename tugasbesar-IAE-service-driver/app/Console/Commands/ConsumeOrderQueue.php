<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Models\Driver;
use App\Models\DriverAssignment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ConsumeOrderQueue extends Command
{
    protected $signature = 'rabbitmq:consume-orders';
    protected $description = 'Listen to RabbitMQ for new orders and assign drivers automatically';

    public function handle()
    {
        $this->info('🚀 Driver Assignment Service - Listening to RabbitMQ...');
        
        try {
            // Koneksi ke RabbitMQ
            $connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'rabbitmq'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASSWORD', 'guest')
            );
            
            $channel = $connection->channel();
            
            // Deklarasi queue yang sama dengan yang digunakan Order Service
            $channel->queue_declare('order_created_queue', false, true, false, false);
            
            $this->info('✅ Connected to RabbitMQ. Waiting for messages...');
            
            // Callback ketika pesan diterima
            $callback = function ($msg) {
                $orderData = json_decode($msg->body, true);
                
                $this->info('📦 New Order Received:');
                $this->info('   - Tracking Number: ' . $orderData['tracking_number']);
                $this->info('   - Customer ID: ' . $orderData['customer_id']);
                $this->info('   - Item: ' . $orderData['item_description']);
                
                // Proses assign driver
                $this->assignDriver($orderData);
                
                // Acknowledge message
                $msg->ack();
            };
            
            // Consume messages
            $channel->basic_qos(null, 1, null);
            $channel->basic_consume('order_created_queue', '', false, false, false, false, $callback);
            
            // Keep listening
            while ($channel->is_consuming()) {
                $channel->wait();
            }
            
            $channel->close();
            $connection->close();
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('RabbitMQ Consumer Error: ' . $e->getMessage());
        }
    }
    
    private function assignDriver($orderData)
    {
        try {
            // 1. Cari driver yang tersedia (status = available)
            $driver = Driver::where('status', 'available')->first();
            
            if (!$driver) {
                $this->warn('⚠️  No available driver found. Creating default driver...');
                
                // Buat driver default jika belum ada
                $driver = Driver::create([
                    'name' => 'Driver Default ' . rand(1, 100),
                    'phone_number' => '081234567890',
                    'status' => 'available'
                ]);
            }
            
            // 2. Buat assignment
            $assignment = DriverAssignment::create([
                'order_id' => $orderData['tracking_number'], // Gunakan tracking_number sebagai order_id
                'driver_id' => $driver->id,
                'status' => 'assigned'
            ]);
            
            // 3. Update status driver menjadi busy
            $driver->update(['status' => 'busy']);
            
            $this->info('✅ Driver Assigned:');
            $this->info('   - Driver: ' . $driver->name);
            $this->info('   - Phone: ' . $driver->phone_number);
            
            // 4. (Optional) Kirim notifikasi ke Order Service untuk update tracking
            $this->sendTrackingUpdate($orderData['tracking_number'], $driver);
            
            // 5. (Optional) Publish ke queue lain untuk notification service
            $this->publishNotification($orderData, $driver);
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to assign driver: ' . $e->getMessage());
            Log::error('Driver Assignment Error: ' . $e->getMessage());
        }
    }
    
    private function sendTrackingUpdate($trackingNumber, $driver)
    {
        try {
            // Panggil GraphQL mutation ke Order Service untuk update tracking
            $response = Http::post(env('ORDER_SERVICE_URL', 'http://order-service:4000/graphql'), [
                'query' => '
                    mutation UpdateTracking($tracking_number: String!, $location: String!, $description: String!, $status: String!) {
                        updateTracking(
                            tracking_number: $tracking_number
                            location: $location
                            description: $description
                            status: $status
                        ) {
                            id
                            tracking_number
                            status
                        }
                    }
                ',
                'variables' => [
                    'tracking_number' => $trackingNumber,
                    'location' => 'Assigned to Driver',
                    'description' => 'Paket telah ditugaskan ke kurir: ' . $driver->name,
                    'status' => 'ASSIGNED_TO_DRIVER'
                ]
            ]);
            
            if ($response->successful()) {
                $this->info('✅ Tracking updated in Order Service');
            } else {
                $this->warn('⚠️  Failed to update tracking: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not update tracking: ' . $e->getMessage());
            Log::warning('Tracking Update Error: ' . $e->getMessage());
        }
    }
    
    private function publishNotification($orderData, $driver)
    {
        try {
            $connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'rabbitmq'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASSWORD', 'guest')
            );
            
            $channel = $connection->channel();
            $channel->queue_declare('driver_assigned_queue', false, true, false, false);
            
            $notificationData = json_encode([
                'event' => 'driver_assigned',
                'tracking_number' => $orderData['tracking_number'],
                'driver_name' => $driver->name,
                'driver_phone' => $driver->phone_number,
                'customer_id' => $orderData['customer_id'],
                'timestamp' => now()->toISOString()
            ]);
            
            $msg = new \PhpAmqpLib\Message\AMQPMessage($notificationData);
            $channel->basic_publish($msg, '', 'driver_assigned_queue');
            
            $this->info('📢 Notification published to driver_assigned_queue');
            
            $channel->close();
            $connection->close();
            
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not publish notification: ' . $e->getMessage());
            Log::warning('Notification Publish Error: ' . $e->getMessage());
        }
    }
}
