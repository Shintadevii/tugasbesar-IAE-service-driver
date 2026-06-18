import { PrismaClient } from '@prisma/client';
import { publishToQueue } from '../rabbitmq'; // 👈 

const prisma = new PrismaClient();

export const resolvers = {
  Query: {
    trackOrder: async (_: any, { tracking_number }: { tracking_number: string }) => {
      return await prisma.order.findUnique({
        where: { tracking_number },
        include: { histories: true },
      });
    },
  },

  Mutation: {
    createOrder: async (_: any, { customer_id, item_description }: { customer_id: string; item_description: string }) => {
      const trackingNumber = `REG-${Date.now()}-${Math.floor(Math.random() * 1000)}`;

      // 1. Simpan data ke Database PostgreSQL
      const newOrder = await prisma.order.create({
        data: {
          tracking_number: trackingNumber,
          customer_id,
          item_description,
          status: 'PENDING',
          histories: {
            create: {
              location: 'Gudang Pengirim',
              description: 'Pesanan telah dibuat dan menunggu pick-up kurir.',
            },
          },
        },
        include: { histories: true },
      });

      // 2. Teriakkan/Kirim pesan ke RabbitMQ bahwa ada orderan baru!
      // 'order_created_queue' adalah nama saluran/grup yang bisa didengarkan oleh service temanmu nanti
      await publishToQueue('order_created_queue', newOrder);

      return newOrder;
    },

    updateTracking: async (
      _: any,
      { tracking_number, location, description, status }: { tracking_number: string; location: string; description: string; status: string }
    ) => {
      const order = await prisma.order.findUnique({
        where: { tracking_number },
      });

      if (!order) {
        throw new Error('Nomor resi tidak ditemukan!');
      }

      return await prisma.order.update({
        where: { tracking_number },
        data: {
          status,
          histories: {
            create: {
              location,
              description,
            },
          },
        },
        include: { histories: true },
      });
    },
  },
};