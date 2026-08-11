# Scheduler Cron Terpusat

Project menyediakan satu module `cron` dengan tiga jadwal. Semua endpoint memakai token yang sama dari Config aplikasi (`cron_token`) dan memiliki database lock agar jadwal yang sama tidak berjalan bersamaan.

## Endpoint

| Jadwal | Endpoint | Rekomendasi cron |
|---|---|---|
| Setiap 5 menit | `/cron/5-minutes` | `*/5 * * * *` |
| Setiap jam | `/cron/hourly` | `7 * * * *` |
| Setiap hari | `/cron/daily` | `10 0 * * *` |

Preview aman kandidat harian tersedia pada `/cron/daily-preview`. Endpoint ini memakai token yang sama, hanya membaca database, dan tidak mengubah PPP Secret maupun MikroTik.

Endpoint lama `/cron/isolation` tetap tersedia dan hanya menjalankan task `customer_isolation` untuk kompatibilitas.

## cPanel Cron Job

Ganti `https://domain-anda.com` dan `TOKEN` sesuai instalasi.

```cron
*/5 * * * * /usr/bin/curl -fsS -H "Authorization: Bearer TOKEN" "https://domain-anda.com/cron/5-minutes" >/dev/null 2>&1
7 * * * * /usr/bin/curl -fsS -H "Authorization: Bearer TOKEN" "https://domain-anda.com/cron/hourly" >/dev/null 2>&1
10 0 * * * /usr/bin/curl -fsS -H "Authorization: Bearer TOKEN" "https://domain-anda.com/cron/daily" >/dev/null 2>&1
```

Query parameter tetap didukung jika hosting tidak mendukung header:

```text
https://domain-anda.com/cron/daily?token=TOKEN
```

Header Authorization lebih disarankan karena token tidak tampil pada URL dan access log biasa.

## Menambahkan Task Baru

Buka:

```text
application/modules/cron/libraries/Cron_scheduler.php
```

Tambahkan entry pada `tasksFor()` sesuai jadwal:

```php
'hourly' => [
    ['key' => 'router_health', 'handler' => 'runRouterHealth'],
],
```

Kemudian buat handler private pada class yang sama. Handler sebaiknya hanya memanggil library domain agar logika bisnis dapat dipakai ulang oleh action manual dan cron:

```php
private function runRouterHealth()
{
    $this->CI->load->library('Router_health');
    return $this->CI->router_health->run();
}
```

Return handler minimal:

```php
['success' => true, 'message' => 'Pemeriksaan selesai.']
```

## Task Aktif

- `daily/customer_isolation`: memanggil `Customer_isolation::run('cron')`.
- `five_minutes`: registry tersedia dan belum memiliki task.
- `hourly`: registry tersedia dan belum memiliki task.

## Database

Jalankan:

```text
DB/2026-07-21_add_centralized_cron_scheduler.sql
```

Tabel `cron_runs` menyimpan ringkasan setiap eksekusi dan `cron_task_logs` menyimpan hasil setiap task. Scheduler tetap dapat berjalan tanpa tabel log untuk membantu proses deployment bertahap, tetapi SQL harus diterapkan agar histori tersedia.

## Pengujian CLI

CLI lokal tidak memerlukan token:

```bash
php index.php cron/cron/five_minutes
php index.php cron/cron/hourly
```

Jangan menjalankan jadwal `daily` pada production hanya untuk tes karena task isolir benar-benar diproses.

Gunakan preview terlebih dahulu:

```bash
curl -fsS -H "Authorization: Bearer TOKEN" "https://domain-anda.com/cron/daily-preview"
```
