// Di Apollo Server terbaru, kita tidak perlu mengimpor 'gql' secara fungsional.
// Kita cukup menulis teks string literal dengan tag #graphql di depannya.

export const typeDefs = `#graphql
  # Definisi objek Riwayat Pelacakan
  type TrackingHistory {
    id: ID!
    order_id: String!
    location: String!
    description: String!
    updated_at: String!
  }

  # Definisi objek Order / Resi
  type Order {
    id: ID!
    tracking_number: String!
    customer_id: String!
    item_description: String!
    status: String!
    created_at: String!
    histories: [TrackingHistory!]
  }

  # Menu untuk mengambil data (Lacak Resi)
  type Query {
    # Mencari paket berdasarkan nomor resi
    trackOrder(tracking_number: String!): Order
  }

  # Menu untuk mengubah/menambah data (Buat Order & Update Posisi)
  type Mutation {
    # Membuat pesanan baru
    createOrder(
      customer_id: String!
      item_description: String!
    ): Order!

    # Memperbarui lokasi paket baru (Update Tracking)
    updateTracking(
      tracking_number: String!
      location: String!
      description: String!
      status: String!
    ): Order!
  }
`;