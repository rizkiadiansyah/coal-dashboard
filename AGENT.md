# Coal Movement Dashboard - Project Notes

Dokumen ini adalah catatan pengingat untuk memahami dan melanjutkan pengembangan proyek.
Perbarui bagian yang relevan ketika fitur, struktur data, atau cara menjalankan aplikasi berubah.

## Gambaran Umum

Coal Movement Dashboard adalah aplikasi internal untuk memantau dan mengelola pergerakan batu bara serta rencana produksi. Aplikasi menyediakan dashboard ringkasan aktivitas aktual, perbandingan plan/actual, dan menu CRUD untuk data operasional.

## Teknologi

- PHP `^8.2` pada deklarasi proyek; dependency vendor saat ini mendeteksi kebutuhan PHP `>=8.4`.
- Laravel `^12.0`.
- Filament `3.3` sebagai admin panel dan UI CRUD.
- Livewire melalui Filament untuk interaksi dashboard.
- Vite `^6`, Tailwind CSS `^4`, dan Laravel Vite Plugin untuk asset frontend.
- MySQL melalui koneksi database terpisah.
- PhpSpreadsheet `^5.8` untuk kebutuhan spreadsheet/import/export yang digunakan oleh modul terkait.
- Laravel Trend `^0.5` untuk agregasi atau tren data.
- PHPUnit `^11.5` untuk testing; Laravel Pint untuk formatting PHP.

## Struktur Penting

- `app/Filament/Pages/`: halaman panel, termasuk dashboard dan login kustom.
- `app/Filament/Resources/`: resource CRUD Filament dan halaman list/create/edit-nya.
- `app/Filament/Widgets/`: kartu statistik, chart, filter tanggal, dan tabel aktivitas dashboard.
- `app/Filament/Widgets/Concerns/`: trait bersama untuk filter periode dan invalidasi cache.
- `app/Models/`: model Eloquent yang memetakan tabel database operasional.
- `app/Providers/Filament/AdminPanelProvider.php`: konfigurasi panel, branding, akses, navigasi, middleware, dan discovery.
- `resources/views/filament/`: Blade view login dan view widget kustom.
- `resources/views/legacy/`: view legacy yang masih tersimpan; cek pemakaian sebelum menghapus.
- `routes/web.php`: route web aplikasi. Saat ini tidak ada route web kustom aktif; panel Filament memakai root path.
- `database/migrations/`: migration default Laravel untuk user/cache/jobs, bukan schema utama database operasional.

## Fitur Utama

### Dashboard

Halaman utama adalah `Coal Movement Dashboard` dan menampilkan:

- Statistik coal movement harian.
- Statistik coal movement bulanan.
- Filter periode tanggal yang disimpan di session.
- Chart plan vs actual OB removal.
- Aktivitas coal getting.
- Aktivitas coal in BMSS Trading.
- Aktivitas coal in outsource.
- Aktivitas crushing.
- Aktivitas hauling CY.
- Aktivitas hauling KA.
- Aktivitas stockpile WBS.
- Tombol refresh untuk menghapus cache widget pada periode aktif dan memicu pembaruan filter.

Widget dashboard memakai cache dengan key berbasis periode dan jam. Saat mengubah logika agregasi, filter, atau format data, periksa juga trait `FiltersDashboardPeriod` dan `InvalidatesDashboardCache` serta action refresh pada halaman dashboard.

### Data Plan dan Operasional

Resource yang terdaftar/disediakan di `app/Filament/Resources/`:

- `CoalGettingResource`: data coal getting aktual.
- `CrusherActivityResource`: aktivitas crushing aktual.
- `HaulingCYResource`: aktivitas hauling ke CY.
- `HaulingKAResource`: aktivitas hauling ke KA.
- `StockpileWBSResource`: transaksi stockpile WBS.
- `PlanCoalGettingResource`: rencana coal getting.
- `PlanCrushingResource`: rencana crushing.
- `PlanHaulingCYResource`: rencana hauling CY.
- `PlanHaulingKAResource`: rencana hauling KA.
- `PlanObRemovalResource`: rencana dan aktual OB removal.
- `PlanBargingWBSResource`: rencana barging WBS.
- `PlanStockpileWBSResource`: rencana stockpile WBS.
- `PlanBMSSTradingResource`: rencana coal in BMSS Trading.
- `PlanOutSourceResource`: rencana coal in outsource.

Resource umumnya mempunyai halaman List, Create, dan Edit. Hak akses resource menggunakan concern `HasFilamentRoleAccess`; baca concern tersebut sebelum menambah atau mengubah aturan akses.

## Model dan Database

Sebagian besar model tidak memakai schema migration lokal karena tabel berada pada database operasional eksternal.

### Koneksi `mysql_cy`

- `User` -> `tbluser_dashboard`, primary key `username`.
- `CoalGetting` -> `tblcoaltransaksimasuk`, primary key `idmasuk`.
- `CrusherActivity` -> `tblcrusheractivity`, primary key `id`.
- `HaulingCY` -> `tblkirimcytransaksikirim`, primary key `idmasuk`.
- `HaulingKA` -> `tblkirimcytransaksikirim_ka`, primary key `idmasuk`.
- `PlanCoalIn` -> `tblplanprodcoalin`, primary key `id`.
- `PlanCrushing` -> `tblplanprodcrush`, primary key `id`.
- `PlanHaulingCY` -> `tblplanprodhaulcy`, primary key `id`.
- `PlanHaulingKA` -> `tblplanprodhaul_ka`, primary key `id`.
- `PlanObRemoval` -> `tblobremoval`, primary key `id`.

### Koneksi `mysql_wbs`

- `PlanBargingWBS` -> `tblplanprodbarging`, primary key `id`.
- `PlanStockpileWBS` -> `tblplanprodstockwbs`, primary key `id`.
- `StockpileWBS` -> `tbltransaksimasuk`, primary key `idmasuk`.

Nama tabel, kolom, primary key, tipe tanggal, dan tipe numerik adalah kontrak dengan database eksternal. Hindari mengubahnya tanpa memastikan schema sumber dan query widget/resource yang terdampak.

## Authentication dan Role

Panel dikonfigurasi di `AdminPanelProvider` dengan:

- Brand name `Coal Movement`.
- URL panel pada root `/`.
- Login kustom `app/Filament/Pages/Auth/Login.php`.
- Primary identifier login berupa `username`, bukan email.
- User aktif (`active === '1'`) dan role yang valid saja yang dapat masuk.
- Role yang tersedia: `1` Admin, `2` Management, `3` MCR.
- Navigation item tertentu, termasuk `Coal In`, disembunyikan untuk Management.
- Warna primary Filament menggunakan Amber.

Saat menambah fitur baru, bedakan tiga hal: boleh login ke panel (`User::canAccessPanel`), boleh melihat resource (`HasFilamentRoleAccess`), dan visibilitas menu/navigation. Ketiganya tidak otomatis sama.

## Konfigurasi Panel

`AdminPanelProvider` melakukan discovery otomatis untuk resource, page, dan widget di bawah `app/Filament`. Middleware panel mencakup session, CSRF, binding, autentikasi, serta event Filament. Lebar konten maksimum full dan sidebar dapat dilipat di desktop.

## Frontend dan View

- Asset utama berada di `resources/css/app.css` dan `resources/js/app.js`.
- Build asset memakai Vite.
- View login kustom berada di `resources/views/filament/pages/auth/login.blade.php`.
- Widget dashboard menggunakan beberapa Blade view kustom, termasuk chart aktivitas, statistik coal movement, filter dashboard, dan informasi waktu update.
- Pertahankan pola UI Filament/Livewire yang sudah ada sebelum menambahkan JavaScript frontend khusus.

## Cara Menjalankan

Prasyarat umum:

1. PHP sesuai kebutuhan dependency vendor, Composer, Node.js/npm, dan MySQL.
2. Salin `.env.example` menjadi `.env` bila belum ada.
3. Isi konfigurasi koneksi database `mysql_cy` dan `mysql_wbs` sesuai environment.
4. Pastikan kredensial dan schema database operasional tersedia.

Perintah yang tersedia:

```powershell
composer install
npm install
php artisan key:generate
php artisan migrate
npm run dev
npm run build
php artisan test
composer run dev
```

`composer run dev` menjalankan server Laravel, queue listener, log viewer Pail, dan Vite secara bersamaan.

## Validasi dan Troubleshooting

- Sebelum menguji dashboard, pastikan PHP CLI yang dipakai sama dengan versi yang didukung vendor.
- Pada environment yang terdokumentasi saat ini, `php artisan route:list --compact` gagal karena PHP CLI `8.2.32`, sedangkan dependency vendor meminta PHP `>=8.4`. Upgrade/switch interpreter sebelum menjalankan command Artisan.
- Jika dashboard menampilkan data lama, gunakan tombol refresh atau periksa cache key berbasis periode/jam.
- Jika data kosong, periksa koneksi database, nama tabel, timezone, filter tanggal, dan format kolom tanggal pada database sumber.
- Jika login gagal, periksa `username`, nilai `active`, role, konfigurasi guard/provider, dan koneksi `mysql_cy`.
- Jangan menganggap migration default Laravel membuat tabel operasional; model-model utama menunjuk ke tabel yang sudah ada di database eksternal.

## Konvensi Pengembangan

- Ikuti struktur Filament yang sudah ada: resource di `app/Filament/Resources`, page di subfolder `Pages`, dan widget di `app/Filament/Widgets`.
- Reuse concern/helper yang sudah ada sebelum membuat logika filter atau cache baru.
- Pertahankan nama tabel, koneksi, primary key, dan casting model kecuali ada perubahan schema yang disengaja.
- Untuk perubahan dashboard, uji perubahan filter, empty state, timezone Asia/Jakarta, cache, dan refresh.
- Untuk perubahan akses, uji Admin, Management, MCR, user nonaktif, dan role tidak dikenal.
- Jalankan formatter/test/build yang relevan setelah perubahan.

## Catatan Pengembangan Lanjutan

- README bawaan masih berisi dokumentasi template Laravel dan belum menjadi dokumentasi domain aplikasi.
- Route web kustom belum digunakan; rute panel Filament dikelola oleh provider.
- Model resource `PlanBMSSTrading` dan `PlanOutSource` belum terlihat di `app/Models`; telusuri implementasi resource/query-nya sebelum melakukan perubahan pada kedua modul tersebut.
- Dokumentasi ini dibuat berdasarkan source yang tersedia; detail schema kolom eksternal perlu dikonfirmasi dari database aktual.