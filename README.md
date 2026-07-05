# 🏢 SIPEKA — Sistem Informasi Penggajian Karyawan

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white"/>
  <img src="https://img.shields.io/badge/Bootstrap_CSS-Custom-27ae60?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge"/>
</p>

<p align="center">
  Aplikasi web berbasis <strong>PHP Native + MySQL</strong> untuk mengelola penggajian dan pinjaman karyawan secara efisien, lengkap dengan slip gaji yang dapat dicetak.
</p>

---

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Struktur Folder](#-struktur-folder)
- [Skema Database](#-skema-database)
- [Cara Instalasi](#-cara-instalasi)
- [Akun Default](#-akun-default)
- [Screenshot](#-screenshot)
- [Alur Penggunaan](#-alur-penggunaan)
- [Kontribusi](#-kontribusi)

---

## ✨ Fitur Utama

| Modul | Fitur |
|-------|-------|
| 🔐 **Autentikasi** | Login/logout dengan session, password hashing `bcrypt` |
| 📊 **Dashboard** | Statistik total karyawan, total gaji bulan ini, pinjaman aktif |
| 👔 **Master Jabatan** | CRUD jabatan + gaji pokok + tunjangan makan |
| 👤 **Master Karyawan** | CRUD karyawan dengan dropdown jabatan dinamis |
| 💰 **Transaksi Penggajian** | Proses gaji bulanan dengan preview sebelum simpan |
| 🏦 **Manajemen Pinjaman** | Pengajuan pinjaman, cicilan otomatis, update status lunas |
| 📄 **Slip Gaji** | Cetak slip profesional 1 halaman A4, lengkap dengan terbilang & TTD |
| 📑 **Laporan** | Rekap penggajian per periode dengan filter bulan/tahun |

---

## 🛠 Teknologi

- **Backend** : PHP 8.2 Native (tanpa framework)
- **Database** : MySQL 8 via `mysqli` (Prepared Statements)
- **Frontend** : HTML5, CSS3 Custom, Vanilla JavaScript
- **Server** : Apache (XAMPP)
- **Keamanan** : `password_hash()`, `password_verify()`, `mysqli_prepare()`, session guard

---

## 📁 Struktur Folder

```
sipeka/
├── 📂 assets/
│   └── style.css               # Global stylesheet
├── 📂 config/
│   └── koneksi.php             # Koneksi database
├── 📂 includes/
│   ├── header.php              # Session guard + HTML head
│   └── sidebar.php             # Navigasi sidebar
├── 📂 modul/
│   ├── 📂 jabatan/             # CRUD Master Jabatan
│   ├── 📂 karyawan/            # CRUD Master Karyawan
│   ├── 📂 pinjaman/            # CRUD Pinjaman
│   └── 📂 penggajian/         # Transaksi Penggajian
├── 📂 laporan/
│   ├── index.php               # Rekap laporan gaji
│   └── slip_gaji.php           # Cetak slip gaji (standalone)
├── database.sql                # Script SQL database
├── index.php                   # Dashboard
├── login.php                   # Halaman login
└── logout.php                  # Proses logout
```

---

## 🗄 Skema Database

```sql
db_penggajian
├── jabatan      (id_jabatan, nama_jabatan, gapok, tunjangan_makan)
├── karyawan     (id_karyawan, nik, nama_karyawan, id_jabatan FK, tgl_masuk)
├── pinjaman     (id_pinjaman, id_karyawan FK, jumlah_pinjaman, tenor,
│                 cicilan_per_bulan, status ENUM('Aktif','Lunas'))
├── penggajian   (id_penggajian, id_karyawan FK, bulan_tahun,
│                 potongan_pinjaman, gaji_bersih)
└── users        (id, username, password)
```

**Relasi:**
```
jabatan ──< karyawan ──< pinjaman
                    └──< penggajian
```

---

## 🚀 Cara Instalasi

### Prasyarat
- XAMPP (Apache + MySQL + PHP 8.x)
- Browser modern (Chrome/Firefox/Edge)

### Langkah-langkah

**1. Clone repository**
```bash
git clone https://github.com/napoleones1/Template-LSP-CRUD.git
```

**2. Pindahkan ke folder htdocs**
```
Salin folder hasil clone ke:
C:\xampp\htdocs\pelatihan\sipeka\
```

**3. Import database**
```
1. Buka http://localhost/phpmyadmin
2. Klik tab "Import"
3. Pilih file: database.sql
4. Klik "Go"
```

**4. Jalankan aplikasi**
```
Buka browser → http://localhost/pelatihan/sipeka/login.php
```

---

## 🔑 Akun Default

| Username | Password | Role |
|----------|----------|------|
| `admin`  | `admin123` | Administrator |

> ⚠️ Disarankan mengganti password setelah pertama login.

---

## 📸 Screenshot

### Dashboard
> Menampilkan ringkasan statistik: total karyawan, penggajian bulan ini, dan pinjaman aktif.

### Manajemen Karyawan
> CRUD lengkap dengan dropdown jabatan dinamis dari database.

### Proses Penggajian
> Pilih karyawan → pilih periode → preview kalkulasi → simpan.

**Formula:**
```
Gaji Bersih = Gaji Pokok + Tunjangan Makan − Cicilan Pinjaman
```

### Slip Gaji (Print)
> Desain profesional 1 halaman A4, berisi:
> - Informasi perusahaan & karyawan
> - Rincian pendapatan & potongan
> - Total gaji bersih + terbilang
> - Kolom tanda tangan (Karyawan, HRD, Direktur)

---

## 🔄 Alur Penggunaan

```
1. Login
       ↓
2. Tambah Jabatan  →  Isi gaji pokok & tunjangan
       ↓
3. Tambah Karyawan →  Pilih jabatan, isi NIK & tgl masuk
       ↓
4. (Opsional) Tambah Pinjaman → Isi jumlah & tenor
       ↓
5. Proses Penggajian → Pilih karyawan & periode → Preview → Simpan
       ↓
6. Cetak Slip Gaji  →  Laporan → Cetak Slip
```

---

## 🤝 Kontribusi

Pull request sangat disambut! Untuk perubahan besar, buka issue terlebih dahulu untuk mendiskusikan apa yang ingin diubah.

1. Fork repository ini
2. Buat branch baru: `git checkout -b fitur/nama-fitur`
3. Commit: `git commit -m "Tambah fitur: nama-fitur"`
4. Push: `git push origin fitur/nama-fitur`
5. Buat Pull Request

---

## 📄 Lisensi

Didistribusikan di bawah lisensi **MIT**. Lihat `LICENSE` untuk informasi lebih lanjut.

---

<p align="center">
  Dibuat dengan ❤️ untuk keperluan pelatihan pemrograman PHP & MySQL
  <br/>
  <strong>SIPEKA</strong> — Sistem Informasi Penggajian Karyawan
</p>
