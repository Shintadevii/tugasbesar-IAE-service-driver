import { ApolloServer } from '@apollo/server';
import { startStandaloneServer } from '@apollo/server/standalone';
import { typeDefs } from './graphql/typeDefs';
import { resolvers } from './graphql/resolvers';

async function startServer() {
  const server = new ApolloServer({
    typeDefs,
    resolvers,
  });

  // Server berjalan di port 4000 lokal laptopmu
  const { url } = await startStandaloneServer(server, {
    listen: { port: 4000 },
  });

  console.log(`🚀 Server GraphQL Manual siap berjalan di: ${url}`);
}

startServer().catch((error) => {
  console.error('Gagal menyalakan server:', error);
});