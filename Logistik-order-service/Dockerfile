# 1. Gunakan mesin Node.js (versi ringan)
FROM node:18

# 2. Buat folder bernama /app di dalam mesin virtual Docker
WORKDIR /app

# 3. Masukkan file daftar modul (package.json) lebih dulu
COPY package*.json ./

# 4. Install semua modul Node.js
RUN npm install

# 5. PENTING: Karena kamu pakai Prisma, kita harus generate Prisma Client
COPY prisma ./prisma/
RUN npx prisma generate

# 6. Masukkan seluruh sisa kodinganmu ke dalam mesin virtual
COPY . .

# 7. Beri tahu Docker bahwa aplikasimu butuh pintu 4000
EXPOSE 4000

# 8. Perintah untuk menyalakan server (sama seperti yang kamu lakukan di terminal)
CMD ["npm", "run", "dev"]