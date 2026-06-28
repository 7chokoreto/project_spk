<?php include 'koneksi.php'; ?>
<?php if (!isset($_SESSION['login'])) { header("Location: login.php"); exit; } ?>
<?php
$username = $_SESSION['username'] ?? 'Admin';

// Stats
$totalSiswa      = $koneksi->query("SELECT COUNT(*) as c FROM siswa")->fetch_assoc()['c'];
$penerimaKip     = $koneksi->query("SELECT COUNT(*) as c FROM siswa WHERE kip_kks=5")->fetch_assoc()['c'];
$tidakKip        = $koneksi->query("SELECT COUNT(*) as c FROM siswa WHERE kip_kks=1")->fetch_assoc()['c'];
$avgNilai        = $koneksi->query("SELECT AVG(nilai) as a FROM siswa")->fetch_assoc()['a'];
$avgJarak        = $koneksi->query("SELECT AVG(jarak) as a FROM siswa")->fetch_assoc()['a'];
$avgPenghasilan  = $koneksi->query("SELECT AVG(penghasilan) as a FROM siswa")->fetch_assoc()['a'];
$prestasiTinggi  = $koneksi->query("SELECT COUNT(*) as c FROM siswa WHERE prestasi=5")->fetch_assoc()['c'];

$avgNilai       = $avgNilai       ? round($avgNilai, 1)       : '-';
$avgJarak       = $avgJarak       ? round($avgJarak, 2)       : '-';
$avgPenghasilan = $avgPenghasilan ? round($avgPenghasilan, 1) : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — SPK Zonasi</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --blue-50: #eff6ff;
    --blue-100: #dbeafe;
    --blue-200: #bfdbfe;
    --blue-400: #60a5fa;
    --blue-500: #3b82f6;
    --blue-600: #2563eb;
    --blue-700: #1d4ed8;
    --blue-900: #1e3a8a;
    --white: #ffffff;
    --gray-50: #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --sidebar-w: 240px;
  }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--gray-50);
    color: var(--gray-800);
    display: flex;
    min-height: 100vh;
  }

  /* ── SIDEBAR ── */
  .sidebar {
    width: var(--sidebar-w);
    background: linear-gradient(180deg, var(--blue-700) 0%, var(--blue-900) 100%);
    min-height: 100vh;
    position: fixed;
    top: 0; left: 0;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: 4px 0 24px rgba(30,58,138,0.15);
  }

  .sidebar-brand {
    padding: 28px 24px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }

  .sidebar-brand .logo-wrap {
    display: flex; align-items: center; gap: 12px;
  }

  .logo-icon {
    width: 40px; height: 40px;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(10px);
  }

  .logo-icon svg { width: 20px; height: 20px; stroke: white; fill: none; }

  .sidebar-brand h2 {
    font-size: 16px; font-weight: 700;
    color: white; letter-spacing: -0.3px;
  }

  .sidebar-brand p {
    font-size: 10px; color: rgba(255,255,255,0.5);
    margin-top: 2px; letter-spacing: 0.5px;
  }

  .sidebar-nav { padding: 20px 14px; flex: 1; }

  .nav-label {
    font-size: 10px; font-weight: 600;
    color: rgba(255,255,255,0.35);
    letter-spacing: 1.2px;
    text-transform: uppercase;
    padding: 0 10px;
    margin-bottom: 8px; margin-top: 16px;
  }

  .nav-link {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 12px;
    border-radius: 10px;
    color: rgba(255,255,255,0.65);
    text-decoration: none;
    font-size: 14px; font-weight: 500;
    transition: all 0.2s;
    margin-bottom: 2px;
  }

  .nav-link svg { width: 18px; height: 18px; stroke: currentColor; fill: none; flex-shrink: 0; }

  .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }

  .nav-link.active {
    background: rgba(255,255,255,0.18);
    color: white; font-weight: 600;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.2);
  }

  .sidebar-footer { padding: 16px 14px; border-top: 1px solid rgba(255,255,255,0.08); }

  /* ── MAIN ── */
  .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

  .topbar {
    background: var(--white);
    border-bottom: 1px solid var(--gray-100);
    padding: 0 32px; height: 68px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 50;
  }

  .topbar-left h1 { font-size: 18px; font-weight: 700; color: var(--gray-800); letter-spacing: -0.4px; }
  .topbar-left p  { font-size: 12px; color: var(--gray-400); }
  .topbar-right   { display: flex; align-items: center; gap: 12px; }

  .account-btn {
    display: flex; align-items: center; gap: 10px;
    background: var(--blue-50); border: 1px solid var(--blue-100);
    border-radius: 50px; padding: 7px 14px 7px 7px;
    cursor: pointer; transition: all 0.2s; position: relative;
  }
  .account-btn:hover { background: var(--blue-100); }

  .avatar {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, var(--blue-500), var(--blue-700));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: white;
  }

  .account-name { font-size: 13px; font-weight: 600; color: var(--blue-700); }

  .chevron { width: 14px; height: 14px; stroke: var(--blue-500); fill: none; transition: transform 0.2s; }
  .account-btn.open .chevron { transform: rotate(180deg); }

  .dropdown-menu {
    position: absolute; top: calc(100% + 10px); right: 0;
    background: white; border: 1px solid var(--gray-200);
    border-radius: 14px; padding: 8px; min-width: 200px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
    opacity: 0; visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s cubic-bezier(0.16,1,0.3,1); z-index: 200;
  }
  .dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
  .dropdown-user { padding: 10px 12px 14px; border-bottom: 1px solid var(--gray-100); margin-bottom: 6px; }
  .dropdown-user .du-name { font-size: 14px; font-weight: 700; color: var(--gray-800); }
  .dropdown-user .du-role { font-size: 12px; color: var(--gray-400); }

  /* ── CONTENT ── */
  .content { padding: 32px; flex: 1; }

  .section-title {
    font-size: 14px; font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase; letter-spacing: 0.8px;
    margin-bottom: 16px;
  }

  /* ── STAT CARDS ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
  }

  .stat-card {
    background: white; border-radius: 16px; padding: 22px 24px;
    border: 1px solid var(--gray-100); box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.2s; position: relative; overflow: hidden;
  }
  .stat-card::before {
    content: ''; position: absolute; top: 0; right: 0;
    width: 80px; height: 80px;
    border-radius: 0 16px 0 100%; opacity: 0.06;
  }
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59,130,246,0.1);
    border-color: var(--blue-200);
  }
  .stat-card .sc-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px;
  }
  .stat-card .sc-icon svg { width: 20px; height: 20px; fill: none; }
  .stat-card .sc-value { font-size: 30px; font-weight: 800; letter-spacing: -1px; line-height: 1; margin-bottom: 6px; }
  .stat-card .sc-label { font-size: 13px; color: var(--gray-500); font-weight: 500; }

  .sc-blue   .sc-icon { background: var(--blue-50); }
  .sc-blue   .sc-icon svg { stroke: var(--blue-500); }
  .sc-blue   .sc-value { color: var(--blue-700); }
  .sc-blue::before { background: var(--blue-500); }

  .sc-green  .sc-icon { background: #f0fdf4; }
  .sc-green  .sc-icon svg { stroke: #22c55e; }
  .sc-green  .sc-value { color: #16a34a; }
  .sc-green::before { background: #22c55e; }

  .sc-orange .sc-icon { background: #fff7ed; }
  .sc-orange .sc-icon svg { stroke: #f97316; }
  .sc-orange .sc-value { color: #ea580c; }
  .sc-orange::before { background: #f97316; }

  .sc-purple .sc-icon { background: #faf5ff; }
  .sc-purple .sc-icon svg { stroke: #a855f7; }
  .sc-purple .sc-value { color: #9333ea; }
  .sc-purple::before { background: #a855f7; }

  .sc-teal   .sc-icon { background: #f0fdfa; }
  .sc-teal   .sc-icon svg { stroke: #14b8a6; }
  .sc-teal   .sc-value { color: #0d9488; }
  .sc-teal::before { background: #14b8a6; }

  .sc-yellow .sc-icon { background: #fefce8; }
  .sc-yellow .sc-icon svg { stroke: #eab308; }
  .sc-yellow .sc-value { color: #ca8a04; }
  .sc-yellow::before { background: #eab308; }

  .sc-rose   .sc-icon { background: #fff1f2; }
  .sc-rose   .sc-icon svg { stroke: #f43f5e; }
  .sc-rose   .sc-value { color: #e11d48; }
  .sc-rose::before { background: #f43f5e; }

  /* ── INFO CARDS ── */
  .info-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
  }

  .info-card {
    background: white; border-radius: 16px; padding: 24px;
    border: 1px solid var(--gray-100); box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .info-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
  .info-card .info-sub { font-size: 12px; color: var(--gray-400); margin-bottom: 20px; }

  /* Bobot bar */
  .bobot-item { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
  .bobot-item:last-child { margin-bottom: 0; }
  .bobot-label { font-size: 13px; font-weight: 600; color: var(--gray-600); width: 130px; flex-shrink: 0; }
  .bobot-bar-wrap { flex: 1; background: var(--blue-50); border-radius: 100px; height: 8px; overflow: hidden; }
  .bobot-bar { height: 100%; background: linear-gradient(90deg, var(--blue-400), var(--blue-600)); border-radius: 100px; transition: width 1s cubic-bezier(0.34,1.56,0.64,1); }
  .bobot-pct { font-size: 12px; font-weight: 700; color: var(--blue-600); width: 36px; text-align: right; }

  /* Parameter list */
  .parameter-item {
    display: flex; align-items: center;
    padding: 11px 0;
    border-bottom: 1px solid var(--gray-100);
    font-size: 13px;
  }
  .parameter-item:last-child { border-bottom: none; }
  .param-dot { width: 8px; height: 8px; border-radius: 50%; margin-right: 12px; flex-shrink: 0; }
  .param-name { color: var(--gray-700); font-weight: 500; flex: 1; }
  .param-type-wrap { display: flex; align-items: center; gap: 6px; }
  .param-type {
    font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 700;
  }
  .type-cost    { background: #fef2f2; color: #dc2626; }
  .type-benefit { background: #f0fdf4; color: #16a34a; }
  .param-bobot  { font-size: 11px; background: var(--blue-50); color: var(--blue-600); padding: 2px 8px; border-radius: 20px; font-weight: 700; }

  /* Welcome banner */
  .welcome-banner {
    background: linear-gradient(135deg, var(--blue-500) 0%, var(--blue-700) 100%);
    border-radius: 20px; padding: 28px 32px; margin-bottom: 28px;
    display: flex; align-items: center; justify-content: space-between;
    position: relative; overflow: hidden;
  }
  .welcome-banner::before {
    content: ''; position: absolute;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,0.06); right: -40px; top: -60px;
  }
  .welcome-banner::after {
    content: ''; position: absolute;
    width: 120px; height: 120px; border-radius: 50%;
    background: rgba(255,255,255,0.06); right: 80px; bottom: -40px;
  }
  .wb-text h2 { font-size: 22px; font-weight: 800; color: white; letter-spacing: -0.5px; margin-bottom: 6px; }
  .wb-text p  { font-size: 14px; color: rgba(255,255,255,0.7); }
  .wb-badge {
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px; padding: 12px 20px; text-align: center;
    backdrop-filter: blur(10px); z-index: 1;
  }
  .wb-badge .wb-date  { font-size: 11px; color: rgba(255,255,255,0.6); font-weight: 500; }
  .wb-badge .wb-today { font-size: 15px; color: white; font-weight: 700; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo-wrap">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      </div>
      <div>
        <h2>Aplikasi SPK Zonasi Sekolsh Menengah Atas</h2>
        <p>SISTEM PENDUKUNG KEPUTUSAN</p>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <a href="dashboard.php" class="nav-link active">
      <svg viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="siswa.php" class="nav-link">
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

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <h1>Dashboard</h1>
      <p>Ringkasan informasi sistem</p>
    </div>
    <div class="topbar-right">
      <div class="account-btn" id="accountBtn" onclick="toggleDropdown()">
        <div class="avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
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

    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div class="wb-text">
        <h2>Halo, <?= htmlspecialchars($username) ?>! 👋</h2>
        <p>Selamat Datang di Aplikasi SPK Penentuan Penerimaan Siswa SMAN 12 Kota Tangerang Berdasarkan Zonasi menggunakan metode TOPSIS</p>
      </div>
      <div class="wb-badge">
        <div class="wb-date">Hari ini</div>
        <div class="wb-today"><?= date('d M Y') ?></div>
      </div>
    </div>

    <!-- Stats Cards -->
    <p class="section-title">Statistik Data</p>
    <div class="stats-grid">

      <div class="stat-card sc-blue">
        <div class="sc-icon">
          <svg viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="sc-value"><?= $totalSiswa ?></div>
        <div class="sc-label">Total Siswa</div>
      </div>

      <div class="stat-card sc-green">
        <div class="sc-icon">
          <svg viewBox="0 0 24 24" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="sc-value"><?= $penerimaKip ?></div>
        <div class="sc-label">Penerima KIP/KKS</div>
      </div>

      <div class="stat-card sc-orange">
        <div class="sc-icon">
          <svg viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="sc-value"><?= $tidakKip ?></div>
        <div class="sc-label">Non KIP/KKS</div>
      </div>

      <div class="stat-card sc-purple">
        <div class="sc-icon">
          <svg viewBox="0 0 24 24" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="sc-value"><?= $avgNilai ?></div>
        <div class="sc-label">Rata-rata Nilai</div>
      </div>

      <div class="stat-card sc-teal">
        <div class="sc-icon">
          <svg viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
        </div>
        <div class="sc-value"><?= $avgJarak ?></div>
        <div class="sc-label">Rata-rata Jarak (km)</div>
      </div>

      <div class="stat-card sc-yellow">
        <div class="sc-icon">
          <svg viewBox="0 0 24 24" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </div>
        <div class="sc-value"><?= $prestasiTinggi ?></div>
        <div class="sc-label">Siswa Berprestasi Tinggi</div>
      </div>

      <div class="stat-card sc-rose">
        <div class="sc-icon">
          <svg viewBox="0 0 24 24" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="sc-value"><?= $avgPenghasilan !== '-' ? $avgPenghasilan . ' jt' : '-' ?></div>
        <div class="sc-label">Rata-rata Penghasilan Ortu (jt)</div>
      </div>

    </div>

    <!-- Info Cards -->
    <p class="section-title">Informasi Sistem</p>
    <div class="info-grid">

      <!-- Bobot Kriteria -->
      <div class="info-card">
        <h3>Bobot Kriteria TOPSIS</h3>
        <p class="info-sub">Distribusi bobot 5 kriteria TOPSIS (total = 100%)</p>

        <div class="bobot-item">
          <span class="bobot-label">C1 – Jarak Rumah</span>
          <div class="bobot-bar-wrap"><div class="bobot-bar" style="width:25%"></div></div>
          <span class="bobot-pct">25%</span>
        </div>
        <div class="bobot-item">
          <span class="bobot-label">C2 – Nilai Rapor</span>
          <div class="bobot-bar-wrap"><div class="bobot-bar" style="width:25%"></div></div>
          <span class="bobot-pct">25%</span>
        </div>
        <div class="bobot-item">
          <span class="bobot-label">C3 – Prestasi</span>
          <div class="bobot-bar-wrap"><div class="bobot-bar" style="width:15%"></div></div>
          <span class="bobot-pct">15%</span>
        </div>
        <div class="bobot-item">
          <span class="bobot-label">C4 – Penghasilan Ortu</span>
          <div class="bobot-bar-wrap"><div class="bobot-bar" style="width:20%"></div></div>
          <span class="bobot-pct">20%</span>
        </div>
        <div class="bobot-item">
          <span class="bobot-label">C5 – KIP/KKS</span>
          <div class="bobot-bar-wrap"><div class="bobot-bar" style="width:15%"></div></div>
          <span class="bobot-pct">15%</span>
        </div>
      </div>

      <!-- Parameter Penilaian -->
      <div class="info-card">
        <h3>Parameter Penilaian</h3>
        <p class="info-sub">Kriteria, tipe atribut, dan bobot yang digunakan</p>

        <div class="parameter-item">
          <div class="param-dot" style="background:#60a5fa"></div>
          <span class="param-name">Jarak Rumah ke Sekolah</span>
          <div class="param-type-wrap">
            <span class="param-type type-cost">Cost</span>
            <span class="param-bobot">25%</span>
          </div>
        </div>
        <div class="parameter-item">
          <div class="param-dot" style="background:#a855f7"></div>
          <span class="param-name">Nilai Rata-rata Rapor</span>
          <div class="param-type-wrap">
            <span class="param-type type-benefit">Benefit</span>
            <span class="param-bobot">25%</span>
          </div>
        </div>
        <div class="parameter-item">
          <div class="param-dot" style="background:#22c55e"></div>
          <span class="param-name">Prestasi (Akademik/Non-akademik)</span>
          <div class="param-type-wrap">
            <span class="param-type type-benefit">Benefit</span>
            <span class="param-bobot">15%</span>
          </div>
        </div>
        <div class="parameter-item">
          <div class="param-dot" style="background:#eab308"></div>
          <span class="param-name">Penghasilan Orang Tua</span>
          <div class="param-type-wrap">
            <span class="param-type type-cost">Cost</span>
            <span class="param-bobot">20%</span>
          </div>
        </div>
        <div class="parameter-item">
          <div class="param-dot" style="background:#f43f5e"></div>
          <span class="param-name">Penerima KIP/KKS</span>
          <div class="param-type-wrap">
            <span class="param-type type-benefit">Benefit</span>
            <span class="param-bobot">15%</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

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