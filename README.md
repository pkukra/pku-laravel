# Laravel Inertia React

Ini adalah aplikasi Laravel yang menggunakan Inertia.js dengan React sebagai frontend.

## Cara Menjalankan

### 1. Clone Repository

```sh
git clone <repository-url>
cd <nama-folder>
```

### 2. Install Dependensi

```sh
composer install
npm install
```

### 3. Copy File `.env` dan Atur Konfigurasi

```sh
cp .env.example .env
```

Edit file `.env` sesuai kebutuhan, termasuk konfigurasi database.

### 4. Generate Key Aplikasi

```sh
php artisan key:generate
```

### 5. Migrasi Database

```sh
php artisan migrate
```

### 6. Jalankan Server Laravel

```sh
php artisan serve
```

### 7. Jalankan Vite untuk Frontend

```sh
npm run dev
```

Aplikasi sekarang dapat diakses di `http://127.0.0.1:8000`.

---

**Catatan:**

-   Pastikan Anda memiliki `PHP`, `Composer`, `Node.js`, dan `npm` terinstal di sistem Anda.
-   Jika menggunakan database, pastikan database telah dibuat dan dikonfigurasi di `.env`.
-   Gunakan `npm run build` untuk produksi.
