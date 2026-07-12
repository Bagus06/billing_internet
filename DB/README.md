# Database Changes

Setiap perubahan struktur atau data wajib dicatat sebagai file SQL di folder ini.

Format nama file:

```text
Y-m-d_deskripsi_perubahan.sql
```

Contoh:

```text
2026-07-11_add_ont_fields_to_customers.sql
```

Ketentuan:

- Satu file untuk satu perubahan yang saling terkait.
- Gunakan transaksi jika didukung oleh operasi tersebut.
- SQL harus aman dijalankan ulang (idempotent) bila memungkinkan.
- Jangan menyimpan password plaintext, community SNMP, API key, atau kredensial lain.
- Sertakan komentar mengenai tujuan perubahan dan prasyaratnya.
- Perubahan berikutnya menggunakan tanggal saat perubahan dibuat, bukan mengubah file lama.

