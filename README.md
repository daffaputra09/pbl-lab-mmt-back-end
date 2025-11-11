## Instalasi

```bash
composer install
```

### Generate Dokumentasi Swagger

```bash
php bin/generate-openapi.php
```


## Menjalankan Proyek

```bash
php -S localhost:8000 -t public public/index.php
```

- Mode di atas memastikan seluruh request menerus ke router kustom (termasuk `swagger/openapi.json`) saat file fisik tidak ditemukan.
- Alternatif: salin atau symlink `swagger/openapi.json` ke `public/swagger/openapi.json` jika ingin tetap memakai perintah bawaan PHP tanpa router script.

Endpoint penting:

- Dokumentasi Swagger UI: `http://localhost:8000/docs`
- Spesifikasi OpenAPI: `http://localhost:8000/swagger/openapi.json`


## Analisis Struktur Kode

- `public/index.php` – front controller yang memuat autoload Composer, dotenv, dan meneruskan request ke router.
- `routes/api.php` – definisi route REST yang menghubungkan path ke controller.
- `app/Core/Router.php` – router sederhana dengan dukungan parameter path.
- `app/Controllers/` – logika HTTP: `KategoriController` (CRUD kategori dengan anotasi OpenAPI), `DocsController` (Swagger UI & spec).
- `app/Models/` – akses database; `Kategori` menangani query PostgreSQL.
- `config/database.php` – kelas koneksi DB membaca credential dari environment.
- `bin/generate-openapi.php` – script untuk menghasilkan `openapi.json` dengan `zircote/swagger-php`.

## Menambahkan Endpoint Baru

TUTORRRRRRR

1. **Model (opsional)**  
   - Lokasi: `app/Models/`  
   - Buat class baru atau lengkapi yang sudah ada untuk menangani query PostgreSQL terkait tabel baru.

2. **Skema Dokumentasi**  
   - Lokasi: `app/Docs/Schemas/`  
   - Tambahkan `#[OA\Schema]` untuk request/response payload agar bisa direferensikan di controller.

3. **Controller**  
   - Lokasi: `app/Controllers/`  
   - Tambahkan method baru (misal `index`, `store`, `update`, dll.) dengan anotasi `#[OA\Get]`, `#[OA\Post]`, dsb, termasuk parameter/path dan referensi schema.
   - Gunakan helper `App\Http\Request` untuk parsing JSON dan `App\Http\Response` untuk mengembalikan respons.

4. **Route**  
   - Lokasi: `routes/api.php`  
   - Registrasikan path ke controller method menggunakan `$router->get()`, `$router->post()`, dan lain-lain.

5. **Autoload (jika ada class baru)**  
   - Jalankan `composer dump-autoload` agar kelas baru terdeteksi.

6. **Regenerate Swagger**  
   - Jalankan `php bin/generate-openapi.php` untuk memperbarui `swagger/openapi.json`.



