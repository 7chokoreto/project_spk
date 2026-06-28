<?php include 'koneksi.php'; ?>
<?php if (!isset($_SESSION['login'])) { header("Location: login.php"); exit; } ?>
<?php
$username = $_SESSION['username'] ?? 'Admin';
$success  = '';

/*
  KRITERIA EXCEL (5 Kriteria):
  C1 – Jarak (km)            → COST    bobot 0.25  → nilai km asli
  C2 – Nilai Rata-rata       → BENEFIT bobot 0.25  → nilai asli (0-100)
  C3 – Prestasi              → BENEFIT bobot 0.15  → 1=Tidak Ada, 3=Non Akademik, 5=Akademik
  C4 – Penghasilan Ortu (jt) → COST    bobot 0.20  → nilai jt asli
  C5 – KIP/KKS               → BENEFIT bobot 0.15  → 5=Penerima, 1=Tidak
*/

if (isset($_POST['simpan'])) {
    $nama        = $koneksi->real_escape_string($_POST['nama']);
    $jarak       = floatval($_POST['jarak']);
    $nilai       = floatval($_POST['nilai']);
    $prestasi    = intval($_POST['prestasi']);    // 1 / 3 / 5
    $penghasilan = floatval($_POST['penghasilan']);
    $kip_kks     = intval($_POST['kip_kks']);    // 5 = Penerima, 1 = Tidak
    $koneksi->query("INSERT INTO siswa(nama,jarak,nilai,prestasi,penghasilan,kip_kks)
        VALUES('$nama','$jarak','$nilai','$prestasi','$penghasilan','$kip_kks')");
    $success = "Data siswa berhasil ditambahkan!";
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $koneksi->query("DELETE FROM siswa WHERE id=$id");
    header("Location: siswa.php");
    exit;
}

$data = $koneksi->query("SELECT * FROM siswa ORDER BY id ASC");
$cnt  = $koneksi->query("SELECT COUNT(*) as c FROM siswa")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Siswa — SPK Zonasi</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
    --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb;
    --blue-700:#1d4ed8; --blue-900:#1e3a8a; --white:#ffffff;
    --gray-50:#f8fafc; --gray-100:#f1f5f9; --gray-200:#e2e8f0;
    --gray-400:#94a3b8; --gray-500:#64748b; --gray-600:#475569;
    --gray-700:#334155; --gray-800:#1e293b; --sidebar-w:240px;
  }
  body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--gray-50); color:var(--gray-800); display:flex; min-height:100vh; }

  /* ── SIDEBAR ── */
  .sidebar { width:var(--sidebar-w); background:linear-gradient(180deg,var(--blue-700) 0%,var(--blue-900) 100%); min-height:100vh; position:fixed; top:0; left:0; display:flex; flex-direction:column; z-index:100; box-shadow:4px 0 24px rgba(30,58,138,.15); }
  .sidebar-brand { padding:28px 24px 20px; border-bottom:1px solid rgba(255,255,255,.1); }
  .sidebar-brand .logo-wrap { display:flex; align-items:center; gap:12px; }
  .logo-icon { width:40px; height:40px; background:rgba(255,255,255,.15); border-radius:10px; display:flex; align-items:center; justify-content:center; }
  .logo-icon svg { width:20px; height:20px; stroke:white; fill:none; }
  .sidebar-brand h2 { font-size:14px; font-weight:700; color:white; }
  .sidebar-brand p { font-size:10px; color:rgba(255,255,255,.5); margin-top:2px; letter-spacing:.5px; }
  .sidebar-nav { padding:20px 14px; flex:1; }
  .nav-label { font-size:10px; font-weight:600; color:rgba(255,255,255,.35); letter-spacing:1.2px; text-transform:uppercase; padding:0 10px; margin-bottom:8px; margin-top:16px; }
  .nav-link { display:flex; align-items:center; gap:12px; padding:11px 12px; border-radius:10px; color:rgba(255,255,255,.65); text-decoration:none; font-size:14px; font-weight:500; transition:all .2s; margin-bottom:2px; }
  .nav-link svg { width:18px; height:18px; stroke:currentColor; fill:none; flex-shrink:0; }
  .nav-link:hover { background:rgba(255,255,255,.1); color:white; }
  .nav-link.active { background:rgba(255,255,255,.18); color:white; font-weight:600; box-shadow:inset 0 0 0 1px rgba(255,255,255,.2); }
  .sidebar-footer { padding:16px 14px; border-top:1px solid rgba(255,255,255,.08); }

  /* ── MAIN ── */
  .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; min-height:100vh; }
  .topbar { background:var(--white); border-bottom:1px solid var(--gray-100); padding:0 32px; height:68px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; }
  .topbar-left h1 { font-size:18px; font-weight:700; color:var(--gray-800); letter-spacing:-.4px; }
  .topbar-left p { font-size:12px; color:var(--gray-400); }
  .topbar-right { display:flex; align-items:center; gap:12px; }
  .account-btn { display:flex; align-items:center; gap:10px; background:var(--blue-50); border:1px solid var(--blue-100); border-radius:50px; padding:7px 14px 7px 7px; cursor:pointer; transition:all .2s; position:relative; }
  .account-btn:hover { background:var(--blue-100); }
  .avatar { width:32px; height:32px; background:linear-gradient(135deg,var(--blue-500),var(--blue-700)); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:white; }
  .account-name { font-size:13px; font-weight:600; color:var(--blue-700); }
  .chevron { width:14px; height:14px; stroke:var(--blue-500); fill:none; transition:transform .2s; }
  .account-btn.open .chevron { transform:rotate(180deg); }
  .dropdown-menu { position:absolute; top:calc(100% + 10px); right:0; background:white; border:1px solid var(--gray-200); border-radius:14px; padding:8px; min-width:200px; box-shadow:0 12px 40px rgba(0,0,0,.1); opacity:0; visibility:hidden; transform:translateY(-8px); transition:all .2s cubic-bezier(.16,1,.3,1); z-index:200; }
  .dropdown-menu.show { opacity:1; visibility:visible; transform:translateY(0); }
  .dropdown-user { padding:10px 12px 14px; border-bottom:1px solid var(--gray-100); margin-bottom:6px; }
  .dropdown-user .du-name { font-size:14px; font-weight:700; color:var(--gray-800); }
  .dropdown-user .du-role { font-size:12px; color:var(--gray-400); }
  .content { padding:32px; flex:1; }

  /* ── ALERT ── */
  .alert { padding:12px 16px; border-radius:10px; font-size:13px; font-weight:500; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
  .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
  .alert svg { width:16px; height:16px; stroke:currentColor; fill:none; }

  /* ── KRITERIA BOX ── */
  .kriteria-box { background:var(--blue-50); border:1px solid var(--blue-100); border-radius:14px; padding:16px 20px; margin-bottom:24px; }
  .kb-title { font-size:12px; font-weight:700; color:var(--blue-700); margin-bottom:10px; }
  .kb-chips { display:flex; flex-wrap:wrap; gap:8px; }
  .k-chip { display:inline-flex; align-items:center; gap:6px; background:white; border:1px solid var(--blue-200); border-radius:20px; padding:5px 12px; font-size:12px; font-weight:600; color:var(--blue-700); }
  .k-type { font-size:10px; padding:2px 7px; border-radius:10px; font-weight:700; }
  .cost    { background:#fef2f2; color:#dc2626; }
  .benefit { background:#f0fdf4; color:#16a34a; }
  .bobot-tag { background:var(--blue-100); color:var(--blue-700); font-size:10px; padding:2px 7px; border-radius:10px; font-weight:700; }

  /* ── SKALA BOX ── */
  .skala-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
  .skala-card { background:white; border:1px solid var(--gray-100); border-radius:12px; padding:14px 16px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
  .skala-card h4 { font-size:12px; font-weight:700; color:var(--gray-600); margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
  .skala-row { display:flex; justify-content:space-between; align-items:center; padding:4px 0; border-bottom:1px solid var(--gray-100); font-size:12px; }
  .skala-row:last-child { border-bottom:none; }
  .skala-row span:last-child { font-weight:700; background:var(--blue-50); color:var(--blue-700); padding:2px 8px; border-radius:8px; font-size:11px; }

  /* ── FORM ── */
  .form-card { background:white; border-radius:16px; padding:28px; border:1px solid var(--gray-100); box-shadow:0 2px 8px rgba(0,0,0,.04); margin-bottom:28px; }
  .form-card h3 { font-size:16px; font-weight:700; color:var(--gray-800); margin-bottom:4px; }
  .form-card .fc-sub { font-size:12px; color:var(--gray-400); margin-bottom:24px; }
  .form-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
  .field label { display:block; font-size:12px; font-weight:600; color:var(--gray-600); margin-bottom:6px; letter-spacing:.3px; }
  .field .field-hint { font-size:11px; color:var(--gray-400); margin-top:4px; }
  .field input, .field select { width:100%; padding:10px 14px; border:1.5px solid var(--blue-100); border-radius:10px; font-family:inherit; font-size:14px; color:var(--gray-800); background:var(--white); transition:all .2s; outline:none; appearance:none; -webkit-appearance:none; }
  .field input:focus, .field select:focus { border-color:var(--blue-400); box-shadow:0 0 0 4px rgba(96,165,250,.15); }
  .field input::placeholder { color:#c0cde0; }
  .form-actions { margin-top:20px; display:flex; justify-content:flex-end; }
  .btn-primary { display:inline-flex; align-items:center; gap:8px; padding:11px 24px; background:linear-gradient(135deg,var(--blue-500),var(--blue-700)); color:white; font-family:inherit; font-size:14px; font-weight:600; border:none; border-radius:10px; cursor:pointer; box-shadow:0 4px 12px rgba(59,130,246,.3); transition:all .2s; }
  .btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(59,130,246,.4); }
  .btn-primary svg { width:16px; height:16px; stroke:white; fill:none; }

  /* ── TABLE ── */
  .table-card { background:white; border-radius:16px; border:1px solid var(--gray-100); box-shadow:0 2px 8px rgba(0,0,0,.04); overflow:hidden; }
  .table-header { padding:18px 24px; border-bottom:1px solid var(--gray-100); display:flex; align-items:center; justify-content:space-between; }
  .table-header h3 { font-size:15px; font-weight:700; color:var(--gray-800); }
  .table-header p { font-size:12px; color:var(--gray-400); }
  .count-badge { background:var(--blue-50); color:var(--blue-600); font-size:12px; font-weight:700; padding:4px 10px; border-radius:20px; }
  table { width:100%; border-collapse:collapse; }
  thead th { padding:11px 14px; font-size:10px; font-weight:700; color:var(--gray-500); text-transform:uppercase; letter-spacing:.8px; text-align:left; background:var(--gray-50); border-bottom:1px solid var(--gray-100); white-space:nowrap; }
  tbody td { padding:12px 14px; font-size:13px; color:var(--gray-700); border-bottom:1px solid var(--gray-100); }
  tbody tr:last-child td { border-bottom:none; }
  tbody tr:hover { background:var(--blue-50); }

  .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
  .badge-akademik    { background:#ede9fe; color:#7c3aed; }
  .badge-nonakademik { background:#dbeafe; color:#1d4ed8; }
  .badge-tidakada    { background:var(--gray-100); color:var(--gray-500); }
  .badge-kip-ya      { background:#f0fdf4; color:#16a34a; }
  .badge-kip-tidak   { background:#fef2f2; color:#dc2626; }

  .btn-hapus { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; font-size:12px; font-weight:600; color:#dc2626; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; text-decoration:none; transition:all .15s; }
  .btn-hapus:hover { background:#dc2626; color:white; }
  .btn-hapus svg { width:13px; height:13px; stroke:currentColor; fill:none; }

  .empty-state { padding:60px 20px; text-align:center; color:var(--gray-400); }
  .empty-state svg { width:48px; height:48px; stroke:var(--gray-200); fill:none; margin-bottom:12px; }
  .empty-state p { font-size:14px; }

  .section-title { font-size:12px; font-weight:700; color:var(--gray-400); text-transform:uppercase; letter-spacing:1px; margin-bottom:12px; margin-top:24px; display:flex; align-items:center; gap:8px; }
  .section-title::after { content:''; flex:1; height:1px; background:var(--gray-100); }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo-wrap">
      <div class="logo-icon"><svg viewBox="0 0 24 24" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
      <div><h2>Aplikasi SPK Zonasi Sekolah Menengah Atas<p>SISTEM PENDUKUNG KEPUTUSAN</p></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <a href="dashboard.php" class="nav-link">
      <svg viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="siswa.php" class="nav-link active">
      <svg viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Data Siswa
    </a>
    <a href="hitung.php" class="nav-link">
      <svg viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Perhitungan TOPSIS
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="nav-link">
      <svg viewBox="0 0 24 24" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <h1>Data Siswa</h1>
      <p>Kelola data calon peserta didik</p>
    </div>
    <div class="topbar-right">
      <div class="account-btn" id="accountBtn" onclick="toggleDropdown()">
        <div class="avatar"><?= strtoupper(substr($username,0,1)) ?></div>
        <span class="account-name"><?= htmlspecialchars($username) ?></span>
        <svg class="chevron" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        <div class="dropdown-menu" id="dropdownMenu">
          <div class="dropdown-user">
            <div class="du-name"><?= htmlspecialchars($username) ?></div>
            <div class="du-role">Administrator</div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="content">

    <?php if ($success): ?>
    <div class="alert alert-success">
      <svg viewBox="0 0 24 24" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <?= $success ?>
    </div>
    <?php endif; ?>

    <!-- Kriteria Box -->
    <div class="kriteria-box">
      <div class="kb-title">📋 Kriteria Penilaian TOPSIS (5 Kriteria) — sesuai Excel</div>
      <div class="kb-chips">
        <span class="k-chip">C1 – Jarak (km) <span class="k-type cost">Cost</span><span class="bobot-tag">0.25</span></span>
        <span class="k-chip">C2 – Nilai Rata-rata <span class="k-type benefit">Benefit</span><span class="bobot-tag">0.25</span></span>
        <span class="k-chip">C3 – Prestasi <span class="k-type benefit">Benefit</span><span class="bobot-tag">0.15</span></span>
        <span class="k-chip">C4 – Penghasilan Orang Tua <span class="k-type cost">Cost</span><span class="bobot-tag">0.20</span></span>
        <span class="k-chip">C5 – KIP/KKS <span class="k-type benefit">Benefit</span><span class="bobot-tag">0.15</span></span>
      </div>
    </div>

    <!-- Tabel Skala Konversi -->
    <p class="section-title">Tabel Skala Konversi</p>
    <div class="skala-grid">
      <div class="skala-card">
        <h4>C1 – Jarak (km)</h4>
        <div class="skala-row"><span>4.0 – 5.0 km</span><span>Skala 5</span></div>
        <div class="skala-row"><span>5.1 – 6.0 km</span><span>Skala 4</span></div>
        <div class="skala-row"><span>6.1 – 7.0 km</span><span>Skala 3</span></div>
        <div class="skala-row"><span>7.1 – 10.0 km</span><span>Skala 2</span></div>
        <div class="skala-row"><span>&gt; 10.0 km</span><span>Skala 1</span></div>
      </div>
      <div class="skala-card">
        <h4>C2 – Nilai Rata-rata</h4>
        <div class="skala-row"><span>&gt; 85</span><span>Skala 5</span></div>
        <div class="skala-row"><span>81 – 85</span><span>Skala 4</span></div>
        <div class="skala-row"><span>76 – 80</span><span>Skala 3</span></div>
        <div class="skala-row"><span>70 – 75</span><span>Skala 2</span></div>
        <div class="skala-row"><span>&lt; 70</span><span>Skala 1</span></div>
      </div>
      <div class="skala-card">
        <h4>C3 – Prestasi</h4>
        <div class="skala-row"><span>Akademik</span><span>Skala 5</span></div>
        <div class="skala-row"><span>Non Akademik</span><span>Skala 3</span></div>
        <div class="skala-row"><span>Tidak Ada</span><span>Skala 1</span></div>
      </div>
      <div class="skala-card">
        <h4>C4 – Penghasilan Ortu</h4>
        <div class="skala-row"><span>≤ 2 jt</span><span>Skala 5</span></div>
        <div class="skala-row"><span>&gt; 2 – 4 jt</span><span>Skala 4</span></div>
        <div class="skala-row"><span>&gt; 4 – 6 jt</span><span>Skala 3</span></div>
        <div class="skala-row"><span>&gt; 6 – 8 jt</span><span>Skala 2</span></div>
        <div class="skala-row"><span>&gt; 8 jt</span><span>Skala 1</span></div>
      </div>
      <div class="skala-card">
        <h4>C5 – KIP/KKS</h4>
        <div class="skala-row"><span>Penerima KIP/KKS</span><span>Skala 5</span></div>
        <div class="skala-row"><span>Tidak</span><span>Skala 1</span></div>
      </div>
    </div>

    <!-- Form Tambah -->
    <p class="section-title">Tambah Data</p>
    <div class="form-card">
      <h3>Tambah Data Siswa</h3>
      <p class="fc-sub">Isi semua kolom berikut — nilai diinput sesuai tabel skala konversi di atas</p>
      <form method="POST">
        <div class="form-grid">
          <!-- Nama -->
          <div class="field">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" placeholder="Nama lengkap siswa" required>
          </div>
          <!-- C1: Jarak -->
          <div class="field">
            <label>C1 – Jarak ke Sekolah (km) <span style="color:#dc2626;font-size:10px">COST</span></label>
            <input type="number" step="0.1" min="0" name="jarak" placeholder="contoh: 5.1" required>
            <div class="field-hint">Masukkan jarak dalam km (nilai asli, bukan skala)</div>
          </div>
          <!-- C2: Nilai -->
          <div class="field">
            <label>C2 – Nilai Rata-rata Rapor <span style="color:#16a34a;font-size:10px">BENEFIT</span></label>
            <input type="number" step="0.01" min="0" max="100" name="nilai" placeholder="contoh: 78.5" required>
            <div class="field-hint">Nilai rata-rata rapor semester terakhir (nilai asli)</div>
          </div>
          <!-- C3: Prestasi -->
          <div class="field">
            <label>C3 – Prestasi <span style="color:#16a34a;font-size:10px">BENEFIT</span></label>
            <select name="prestasi" required>
              <option value="">-- Pilih Prestasi --</option>
              <option value="5">Akademik (Skala 5)</option>
              <option value="3">Non Akademik (Skala 3)</option>
              <option value="1">Tidak Ada (Skala 1)</option>
            </select>
            <div class="field-hint">Prestasi akademik atau non-akademik siswa</div>
          </div>
          <!-- C4: Penghasilan -->
          <div class="field">
            <label>C4 – Penghasilan Orang Tua (jt/bulan) <span style="color:#dc2626;font-size:10px">COST</span></label>
            <input type="number" step="0.1" min="0" name="penghasilan" placeholder="contoh: 3.5" required>
            <div class="field-hint">Penghasilan bulanan orang tua dalam juta rupiah (nilai asli)</div>
          </div>
          <!-- C5: KIP/KKS -->
          <div class="field">
            <label>C5 – Penerima KIP/KKS <span style="color:#16a34a;font-size:10px">BENEFIT</span></label>
            <select name="kip_kks" required>
              <option value="">-- Pilih Status --</option>
              <option value="5">Penerima KIP/KKS (Skala 5)</option>
              <option value="1">Tidak (Skala 1)</option>
            </select>
            <div class="field-hint">Apakah siswa adalah penerima KIP atau KKS?</div>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" name="simpan" class="btn-primary">
            <svg viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Simpan Data
          </button>
        </div>
      </form>
    </div>

    <!-- Tabel Daftar -->
    <div class="table-card">
      <div class="table-header">
        <div>
          <h3>Daftar Siswa</h3>
          <p>Semua data siswa yang terdaftar</p>
        </div>
        <span class="count-badge"><?= $cnt ?> Siswa</span>
      </div>
      <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nama</th>
            <th>C1 – Jarak (km)</th>
            <th>C2 – Nilai</th>
            <th>C3 – Prestasi</th>
            <th>C4 – Penghasilan (jt)</th>
            <th>C5 – KIP/KKS</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; while($row = $data->fetch_assoc()): ?>
          <tr>
            <td style="color:var(--gray-400);font-weight:600"><?= $no++ ?></td>
            <td style="font-weight:600;color:var(--gray-800)"><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= $row['jarak'] ?> km</td>
            <td><?= $row['nilai'] ?></td>
            <td>
              <?php
                $p = intval($row['prestasi']);
                if ($p == 5)      echo '<span class="badge badge-akademik">🏆 Akademik</span>';
                elseif ($p == 3)  echo '<span class="badge badge-nonakademik">📋 Non Akademik</span>';
                else              echo '<span class="badge badge-tidakada">— Tidak Ada</span>';
              ?>
            </td>
            <td>Rp <?= number_format($row['penghasilan'],1) ?> jt</td>
            <td>
              <?php if(intval($row['kip_kks']) == 5): ?>
                <span class="badge badge-kip-ya">✓ Penerima</span>
              <?php else: ?>
                <span class="badge badge-kip-tidak">✗ Tidak</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus data <?= htmlspecialchars($row['nama']) ?>?')">
                <svg viewBox="0 0 24 24" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Hapus
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if($cnt == 0): ?>
          <tr><td colspan="8">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              <p>Belum ada data siswa. Tambahkan data di atas.</p>
            </div>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<script>
function toggleDropdown() {
  const btn  = document.getElementById('accountBtn');
  const menu = document.getElementById('dropdownMenu');
  btn.classList.toggle('open');
  menu.classList.toggle('show');
}
document.addEventListener('click', function(e) {
  if (!document.getElementById('accountBtn').contains(e.target)) {
    document.getElementById('accountBtn').classList.remove('open');
    document.getElementById('dropdownMenu').classList.remove('show');
  }
});
</script>
</body>
</html>
