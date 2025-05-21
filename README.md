# Telenavi Task - Todo Management API

Sistem API untuk mengelola tugas (todo) dengan berbagai fitur seperti pengkategorian berdasarkan status, prioritas, dan penanggung jawab (assignee). API ini menyediakan kemampuan dasar CRUD, exportasi data, serta visualisasi dalam bentuk chart.

## Fitur

- **CRUD Todo**: Membuat, membaca, memperbarui dan menghapus todo
- **Data Ekspor**: Ekspor data todo ke format CSV/Excel
- **Chart Data**: Menyediakan data untuk visualisasi statistik todo

## Endpoints

### Todo Management

- `GET /api/v1/todos` - Mendapatkan daftar todo dengan filter dan pagination
- `POST /api/v1/todos` - Membuat todo baru
- `GET /api/v1/todos/{id}` - Mendapatkan detail todo berdasarkan ID
- `PUT /api/v1/todos/{id}` - Memperbarui todo yang ada
- `DELETE /api/v1/todos/{id}` - Menghapus todo

### Data Export

- `GET /api/v1/todos/export` - Ekspor data todo ke format CSV/Excel

### Chart Data

- `GET /api/v1/todos/chart?type=status` - Mendapatkan ringkasan jumlah todo berdasarkan status
- `GET /api/v1/todos/chart?type=priority` - Mendapatkan ringkasan jumlah todo berdasarkan prioritas
- `GET /api/v1/todos/chart?type=assignee` - Mendapatkan ringkasan todo berdasarkan penanggung jawab

## Instalasi

1. Clone repositori:
   ```bash
   git clone https://github.com/yogaap24/telenavi-test
   cd telenavi-test
   ```

2. Install dependensi:
   ```bash
   composer install
   ```

3. Salin file .env:
   ```bash
   cp .env.example .env
   ```

4. Konfigurasi database di file `.env`

5. Generate application key:
   ```bash
   php artisan key:generate
   ```

6. Jalankan migrasi:
   ```bash
   php artisan migrate
   ```

7. (Opsional) Isi database dengan data contoh:
   ```bash
   php artisan db:seed
   ```

## Menjalankan Aplikasi

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

## Teknologi yang Digunakan

- **Framework**: Laravel
- **Database**: PostgreSQL/MySQL
- **Paket Tambahan**:
  - PhpOffice/PhpSpreadsheet (untuk ekspor Excel)
  - Laravel Sanctum (untuk autentikasi API)

## Kebutuhan Sistem

- PHP >= 8.0
- Composer
- Database PostgreSQL/MySQL
- Ekstensi PHP: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
