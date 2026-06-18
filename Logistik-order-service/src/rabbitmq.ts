import amqp from 'amqplib';

// Gunakan environment variable jika ada, jika tidak gunakan default jalur Docker
const RABBITMQ_URL = process.env.RABBITMQ_URL || 'amqp://guest:guest@rabbitmq:5672';

export async function publishToQueue(queueName: string, data: any) {
  try {
    // 1. Membuka koneksi ke RabbitMQ
    const connection = await amqp.connect(RABBITMQ_URL);
    const channel = await connection.createChannel();

    // 2. Memastikan antrian (queue) tersedia
    await channel.assertQueue(queueName, { durable: true });

    // 3. Mengirim data pesanan ke dalam antrian
    channel.sendToQueue(queueName, Buffer.from(JSON.stringify(data)));
    console.log(`\n✅ [RabbitMQ] Pesan berhasil dikirim ke antrian: ${queueName}`);

    // Tutup koneksi setelah setengah detik agar tidak membebani memori
    setTimeout(() => {
      connection.close();
    }, 500);
  } catch (error) {
    console.error('❌ Gagal mengirim pesan ke RabbitMQ:', error);
  }
}