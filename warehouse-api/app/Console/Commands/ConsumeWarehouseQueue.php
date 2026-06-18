<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Log;

class ConsumeWarehouseQueue extends Command
{
    protected $signature = 'rabbitmq:consume-warehouse';
    protected $description = 'Listen to RabbitMQ for warehouse operations (incoming packages)';

    public function handle()
    {
        $this->info('📦 Warehouse Service - Listening to RabbitMQ...');
        
        try {
            $connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'rabbitmq'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASSWORD', 'guest')
            );
            
            $channel = $connection->channel();
            
            // Listen untuk order yang masuk warehouse
            $channel->queue_declare('order_created_queue', false, true, false, false);
            
            $this->info('✅ Connected to RabbitMQ. Waiting for warehouse operations...');
            
            $callback = function ($msg) {
                $orderData = json_decode($msg->body, true);
                
                $this->info('📥 Package arrived at warehouse:');
                $this->info('   - Tracking: ' . $orderData['tracking_number']);
                $this->info('   - Item: ' . $orderData['item_description']);
                
                // Record di warehouse
                $this->recordWarehouseEntry($orderData);
                
                $msg->ack();
            };
            
            $channel->basic_qos(null, 1, null);
            $channel->basic_consume('order_created_queue', '', false, false, false, false, $callback);
            
            while ($channel->is_consuming()) {
                $channel->wait();
            }
            
            $channel->close();
            $connection->close();
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Warehouse RabbitMQ Consumer Error: ' . $e->getMessage());
        }
    }
    
    private function recordWarehouseEntry($orderData)
    {
        try {
            // Cari atau buat produk berdasarkan item description
            $product = Product::firstOrCreate(
                ['name' => $orderData['item_description']],
                [
                    'sku' => 'SKU-' . strtoupper(substr(md5($orderData['item_description']), 0, 8)),
                    'description' => 'Item dari order: ' . $orderData['tracking_number'],
                    'price' => 0,
                    'category' => 'general'
                ]
            );
            
            // Record di warehouse orders
            $warehouseOrder = Order::create([
                'reference' => $orderData['tracking_number'],
                'status' => 'received', // received, processing, dispatched
                'product_id' => $product->id,
                'quantity' => 1,
                'total' => 0
            ]);
            
            // Update atau create stock
            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id, 'location' => 'Main Warehouse'],
                ['quantity' => 0, 'reorder_level' => 5]
            );
            
            // Increment stock quantity
            $stock->increment('quantity');
            
            $this->info('✅ Warehouse record created:');
            $this->info('   - Product: ' . $product->name);
            $this->info('   - Stock Location: Main Warehouse');
            $this->info('   - Current Stock: ' . $stock->quantity);
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to record warehouse entry: ' . $e->getMessage());
            Log::error('Warehouse Entry Error: ' . $e->getMessage());
        }
    }
}
