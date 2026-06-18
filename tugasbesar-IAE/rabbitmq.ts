import amqp from 'amqplib';

// URL komunikasi di dalam ekosistem Docker
const RABBITMQ_URL = process.env.RABBITMQ_URL || 'amqp://guest:guest@rabbitmq:5672';

// Fungsi untuk mengirim pesan (Misal: User Baru Register)
export async function publishAuthEvent(queueName: string, data: any) {
  try {
    const connection = await amqp.connect(RABBITMQ_URL);
    const channel = await connection.createChannel();

    // Pastikan antrean tersedia
    await channel.assertQueue(queueName, { durable: true });

    // Kirim data
    channel.sendToQueue(queueName, Buffer.from(JSON.stringify(data)));
    console.log(`\n🔐 [Auth Service] Berhasil mengirim data ke antrean: ${queueName}`);

    setTimeout(() => {
      connection.close();
    }, 500);
  } catch (error) {
    console.error('❌ [Auth Service] Gagal menyambung ke RabbitMQ:', error);
  }
}
