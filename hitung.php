<?php include 'koneksi.php'; ?>
<?php if (!isset($_SESSION['login'])) { header("Location: login.php"); exit; } ?>
<?php
$username = $_SESSION['username'] ?? 'Admin';

$data  = $koneksi->query("SELECT * FROM siswa ORDER BY id ASC");
$siswa = [];
while ($row = $data->fetch_assoc()) { $siswa[] = $row; }
$n       = count($siswa);
$hasData = $n > 0;
$result  = [];

if ($hasData) {
    /*
    ================================================================
     METODE TOPSIS — persis sama dengan perhitungan di Excel
    ================================================================
     KRITERIA:
       C1 – Jarak (km)         → COST    bobot: 0.25
       C2 – Nilai Rata-rata    → BENEFIT  bobot: 0.25
       C3 – Prestasi            → BENEFIT  bobot: 0.15
       C4 – Penghasilan Ortu   → COST     bobot: 0.20
       C5 – KIP/KKS             → BENEFIT  bobot: 0.15
    ================================================================
    */
    $bobot = [0.25, 0.25, 0.15, 0.20, 0.15];
    $tipe  = ['COST','BENEFIT','BENEFIT','COST','BENEFIT']; // per kolom

    // Ambil nilai mentah per siswa → matrix X [n][5]
    $X = [];
    foreach ($siswa as $s) {
        $X[] = [
            floatval($s['jarak']),        // C1 – Jarak (km)         COST
            floatval($s['nilai']),        // C2 – Nilai Rata-rata     BENEFIT
            floatval($s['prestasi']),     // C3 – Prestasi (1/3/5)   BENEFIT
            floatval($s['penghasilan']),  // C4 – Penghasilan Ortu   COST
            floatval($s['kip_kks']),      // C5 – KIP/KKS (5/1)      BENEFIT
        ];
    }

    // ── STEP 1: PEMBAGI = sqrt( Σ(xij²) ) per kolom ──────────────
    $pembagi = [0, 0, 0, 0, 0];
    foreach ($X as $row) {
        for ($j = 0; $j < 5; $j++) {
            $pembagi[$j] += $row[$j] ** 2;
        }
    }
    for ($j = 0; $j < 5; $j++) {
        $pembagi[$j] = sqrt($pembagi[$j]);
    }

    // ── STEP 2: MATRIX NORMALISASI R = xij / pembagi_j ──────────
    $R = [];
    foreach ($X as $i => $row) {
        for ($j = 0; $j < 5; $j++) {
            $R[$i][$j] = ($pembagi[$j] != 0) ? $row[$j] / $pembagi[$j] : 0;
        }
    }

    // ── STEP 3: MATRIX TERBOBOT Y = bobot_j × R_ij ───────────────
    $Y = [];
    foreach ($R as $i => $row) {
        for ($j = 0; $j < 5; $j++) {
            $Y[$i][$j] = $bobot[$j] * $row[$j];
        }
    }

    // ── STEP 4: SOLUSI IDEAL POSITIF (A+) ─────────────────────────
    // BENEFIT → MAX kolom Y,  COST → MIN kolom Y
    $Aplus = [];
    for ($j = 0; $j < 5; $j++) {
        $col = array_column($Y, $j);
        $Aplus[$j] = ($tipe[$j] === 'BENEFIT') ? max($col) : min($col);
    }

    // ── STEP 5: SOLUSI IDEAL NEGATIF (A-) ─────────────────────────
    // COST → MAX kolom Y,  BENEFIT → MIN kolom Y
    $Aminus = [];
    for ($j = 0; $j < 5; $j++) {
        $col = array_column($Y, $j);
        $Aminus[$j] = ($tipe[$j] === 'COST') ? max($col) : min($col);
    }

    // ── STEP 6: JARAK D+ dan D- ───────────────────────────────────
    // D+i = sqrt( Σ(A+j − yij)² )
    // D-i = sqrt( Σ(yij − A-j)² )
    $Dplus  = [];
    $Dminus = [];
    foreach ($Y as $i => $row) {
        $sumPlus  = 0;
        $sumMinus = 0;
        for ($j = 0; $j < 5; $j++) {
            $sumPlus  += ($Aplus[$j]  - $row[$j]) ** 2;
            $sumMinus += ($row[$j] - $Aminus[$j]) ** 2;
        }
        $Dplus[$i]  = sqrt($sumPlus);
        $Dminus[$i] = sqrt($sumMinus);
    }

    // ── STEP 7: NILAI PREFERENSI Ci ───────────────────────────────
    // Ci = D-i / (D-i + D+i)
    $Ci = [];
    foreach ($siswa as $i => $s) {
        $denom  = $Dminus[$i] + $Dplus[$i];
        $Ci[$i] = ($denom != 0) ? $Dminus[$i] / $denom : 0;
    }

    // ── STEP 8: RANKING ───────────────────────────────────────────
    // Semakin tinggi Ci → semakin baik
    $CiSorted = $Ci;
    arsort($CiSorted);
    $rankMap = [];
    $rank    = 1;
    foreach ($CiSorted as $i => $val) {
        $rankMap[$i] = $rank++;
    }

    // Gabungkan semua ke $result, urutkan berdasar ranking
    foreach ($siswa as $i => $s) {
        $result[] = array_merge($s, [
            'X'      => $X[$i],
            'R'      => array_map(fn($v) => round($v, 6), $R[$i]),
            'Y'      => array_map(fn($v) => round($v, 6), $Y[$i]),
            'dplus'  => round($Dplus[$i],  6),
            'dminus' => round($Dminus[$i], 6),
            'ci'     => round($Ci[$i],     4),
            'rank'   => $rankMap[$i],
        ]);
    }

    usort($result, fn($a, $b) => $a['rank'] <=> $b['rank']);

    // Simpan A+, A- untuk ditampilkan
    $Aplus_display  = array_map(fn($v) => round($v, 6), $Aplus);
    $Aminus_display = array_map(fn($v) => round($v, 6), $Aminus);
    $pembagi_display = array_map(fn($v) => round($v, 6), $pembagi);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perhitungan TOPSIS — SPK Zonasi</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --blue-50:#eff6ff;--blue-100:#dbeafe;--blue-200:#bfdbfe;
    --blue-400:#60a5fa;--blue-500:#3b82f6;--blue-600:#2563eb;
    --blue-700:#1d4ed8;--blue-900:#1e3a8a;--white:#ffffff;
    --gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;
    --gray-400:#94a3b8;--gray-500:#64748b;--gray-600:#475569;
    --gray-700:#334155;--gray-800:#1e293b;--sidebar-w:240px;
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

  /* ── BANNERS & SECTION TITLES ── */
  .section-title { font-size:12px; font-weight:700; color:var(--gray-400); text-transform:uppercase; letter-spacing:1px; margin-bottom:12px; margin-top:28px; display:flex; align-items:center; gap:8px; }
  .section-title::after { content:''; flex:1; height:1px; background:var(--gray-100); }

  .method-banner { background:linear-gradient(135deg,var(--blue-600),var(--blue-900)); border-radius:16px; padding:24px 28px; margin-bottom:8px; display:flex; align-items:center; gap:20px; overflow:hidden; position:relative; }
  .method-banner::after { content:''; position:absolute; right:-20px; top:-30px; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,.06); }
  .method-icon { width:52px; height:52px; background:rgba(255,255,255,.15); border-radius:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .method-icon svg { width:26px; height:26px; stroke:white; fill:none; }
  .method-text h2 { font-size:18px; font-weight:800; color:white; letter-spacing:-.4px; margin-bottom:4px; }
  .method-text p { font-size:13px; color:rgba(255,255,255,.7); }
  .method-pills { margin-left:auto; display:flex; gap:8px; z-index:1; flex-wrap:wrap; justify-content:flex-end; }
  .pill { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.2); border-radius:20px; padding:6px 14px; font-size:12px; font-weight:600; color:white; }

  /* ── BOBOT CARDS ── */
  .bobot-grid { display:flex; gap:10px; flex-wrap:wrap; }
  .bobot-card { flex:1; min-width:140px; background:white; border-radius:12px; border:1px solid var(--gray-100); padding:14px 16px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
  .bc-code { font-size:11px; font-weight:700; color:var(--gray-400); text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
  .bc-name { font-size:13px; font-weight:600; color:var(--gray-800); margin-bottom:6px; }
  .bc-footer { display:flex; align-items:center; gap:6px; }
  .bc-weight { font-size:18px; font-weight:800; color:var(--blue-600); }
  .bc-type { font-size:10px; font-weight:700; padding:2px 8px; border-radius:10px; }
  .type-cost { background:#fef2f2; color:#dc2626; }
  .type-benefit { background:#f0fdf4; color:#16a34a; }

  /* ── TOPSIS STEP BOXES ── */
  .step-box { background:white; border-radius:14px; border:1px solid var(--gray-100); box-shadow:0 2px 6px rgba(0,0,0,.04); overflow:hidden; margin-bottom:4px; }
  .step-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; cursor:pointer; user-select:none; }
  .step-header:hover { background:var(--gray-50); }
  .step-badge { display:inline-flex; align-items:center; gap:10px; }
  .step-num { width:28px; height:28px; border-radius:8px; background:var(--blue-600); color:white; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .step-title { font-size:14px; font-weight:700; color:var(--gray-800); }
  .step-subtitle { font-size:12px; color:var(--gray-400); margin-top:1px; }
  .step-chevron { width:16px; height:16px; stroke:var(--gray-400); fill:none; transition:transform .2s; }
  .step-header.open .step-chevron { transform:rotate(180deg); }
  .step-body { display:none; padding:0 4px 16px; overflow-x:auto; }
  .step-body.show { display:block; }

  /* ── TABLES ── */
  .tbl-wrap { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; font-size:12px; }
  thead th { padding:10px 12px; font-size:10px; font-weight:700; color:var(--gray-500); text-transform:uppercase; letter-spacing:.8px; text-align:center; background:var(--gray-50); border-bottom:1px solid var(--gray-100); white-space:nowrap; }
  thead th:first-child { text-align:left; }
  tbody td { padding:10px 12px; color:var(--gray-700); border-bottom:1px solid var(--gray-100); text-align:center; white-space:nowrap; }
  tbody td:first-child { text-align:left; font-weight:600; color:var(--gray-800); }
  tbody tr:last-child td { border-bottom:none; }
  tbody tr:hover { background:var(--blue-50); }
  .num { font-variant-numeric:tabular-nums; }

  .highlight-row td { background:#fefce8 !important; }
  .aplus-row td  { background:#f0fdf4 !important; color:#15803d; font-weight:700; }
  .aminus-row td { background:#fef2f2 !important; color:#b91c1c; font-weight:700; }

  /* ── PODIUM ── */
  .podium { display:flex; align-items:flex-end; justify-content:center; gap:12px; }
  .podium-item { text-align:center; flex:1; max-width:200px; }
  .podium-card { border-radius:14px; padding:20px 16px; border:2px solid transparent; }
  .podium-rank { font-size:28px; margin-bottom:6px; }
  .podium-name { font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:4px; }
  .podium-ci { font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; display:inline-block; }
  .podium-1 .podium-card { background:linear-gradient(135deg,#fef9c3,#fef3c7); border-color:#fbbf24; }
  .podium-1 .podium-ci { background:#fef3c7; color:#b45309; }
  .podium-2 .podium-card { background:var(--gray-50); border-color:var(--gray-200); }
  .podium-2 .podium-ci { background:var(--gray-100); color:var(--gray-600); }
  .podium-3 .podium-card { background:#fff7ed; border-color:#fed7aa; }
  .podium-3 .podium-ci { background:#ffedd5; color:#9a3412; }

  /* ── FINAL RANKING TABLE ── */
  .rank-badge { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:8px; font-size:12px; font-weight:800; }
  .rank-1 { background:#fef3c7; color:#d97706; }
  .rank-2 { background:var(--gray-100); color:var(--gray-600); }
  .rank-3 { background:#ffedd5; color:#c2410c; }
  .rank-n { background:var(--blue-50); color:var(--blue-600); }
  .score-bar-wrap { display:flex; align-items:center; gap:8px; }
  .score-bar-bg { flex:1; background:var(--blue-50); border-radius:100px; height:6px; overflow:hidden; min-width:60px; }
  .score-bar-fill { height:100%; background:linear-gradient(90deg,var(--blue-400),var(--blue-600)); border-radius:100px; }
  .score-val { font-weight:700; color:var(--blue-700); font-size:13px; min-width:44px; text-align:right; }
  .badge-diterima { display:inline-flex; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#f0fdf4; color:#16a34a; }
  .badge-tidak    { display:inline-flex; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#fef2f2; color:#dc2626; }

  .table-card { background:white; border-radius:16px; border:1px solid var(--gray-100); box-shadow:0 2px 8px rgba(0,0,0,.04); overflow:hidden; }
  .table-header { padding:18px 20px; border-bottom:1px solid var(--gray-100); display:flex; align-items:center; justify-content:space-between; }
  .table-header h3 { font-size:15px; font-weight:700; color:var(--gray-800); }
  .table-header p { font-size:12px; color:var(--gray-400); }

  .empty-state { padding:80px 20px; text-align:center; color:var(--gray-400); }
  .empty-state svg { width:52px; height:52px; stroke:var(--gray-200); fill:none; margin-bottom:14px; }
  .empty-state h3 { font-size:16px; font-weight:600; margin-bottom:6px; }
  .empty-state a { color:var(--blue-600); text-decoration:none; font-weight:600; }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo-wrap">
      <div class="logo-icon"><svg viewBox="0 0 24 24" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
      <div><h2>Aplikasi SPK Zonasi Sekolah Menengah Atas</h2><p>SISTEM PENDUKUNG KEPUTUSAN</p></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <a href="dashboard.php" class="nav-link">
      <svg viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="siswa.php" class="nav-link">
      <svg viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      Data Siswa
    </a>
    <a href="hitung.php" class="nav-link active">
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
      <h1>Perhitungan TOPSIS</h1>
      <p>Technique for Order of Preference by Similarity to Ideal Solution</p>
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

    <!-- Banner -->
    <div class="method-banner">
      <div class="method-icon"><svg viewBox="0 0 24 24" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
      <div class="method-text">
        <h2>Metode TOPSIS</h2>
        <p>Perhitungan mengikuti langkah-langkah baku TOPSIS: Normalisasi → Terbobot → Solusi Ideal → Jarak → Preferensi</p>
      </div>
      <div class="method-pills">
        <span class="pill">5 Kriteria</span>
        <span class="pill"><?= $n ?> Alternatif</span>
      </div>
    </div>

    <!-- Bobot -->
    <p class="section-title">Bobot &amp; Tipe Kriteria</p>
    <div class="bobot-grid">
      <div class="bobot-card"><div class="bc-code">C1</div><div class="bc-name">Jarak ke Sekolah</div><div class="bc-footer"><span class="bc-weight">25%</span><span class="bc-type type-cost">Cost</span></div></div>
      <div class="bobot-card"><div class="bc-code">C2</div><div class="bc-name">Nilai Rata-rata</div><div class="bc-footer"><span class="bc-weight">25%</span><span class="bc-type type-benefit">Benefit</span></div></div>
      <div class="bobot-card"><div class="bc-code">C3</div><div class="bc-name">Prestasi Siswa</div><div class="bc-footer"><span class="bc-weight">15%</span><span class="bc-type type-benefit">Benefit</span></div></div>
      <div class="bobot-card"><div class="bc-code">C4</div><div class="bc-name">Penghasilan Ortu</div><div class="bc-footer"><span class="bc-weight">20%</span><span class="bc-type type-cost">Cost</span></div></div>
      <div class="bobot-card"><div class="bc-code">C5</div><div class="bc-name">KIP / KKS</div><div class="bc-footer"><span class="bc-weight">15%</span><span class="bc-type type-benefit">Benefit</span></div></div>
    </div>

    <?php if (!$hasData): ?>
    <div class="table-card" style="margin-top:24px">
      <div class="empty-state">
        <svg viewBox="0 0 24 24" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        <h3>Belum Ada Data</h3>
        <p>Tambahkan data siswa terlebih dahulu di <a href="siswa.php">Data Siswa</a></p>
      </div>
    </div>
    <?php else: ?>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- STEP 1 : MATRIX KEPUTUSAN (Data Mentah)            -->
    <!-- ═══════════════════════════════════════════════════ -->
    <p class="section-title">Langkah-langkah Perhitungan TOPSIS</p>

    <div class="step-box">
      <div class="step-header open" onclick="toggleStep(this)">
        <div class="step-badge">
          <span class="step-num">1</span>
          <div><div class="step-title">Matrix Keputusan X (Data Mentah)</div><div class="step-subtitle">Nilai asli setiap alternatif pada masing-masing kriteria</div></div>
        </div>
        <svg class="step-chevron" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="step-body show">
        <div class="tbl-wrap" style="padding:0 16px">
        <table>
          <thead><tr>
            <th>Alternatif</th>
            <th>C1 – Jarak (km)</th><th>C2 – Nilai</th><th>C3 – Prestasi</th><th>C4 – Penghasilan</th><th>C5 – KIP/KKS</th>
          </tr></thead>
          <tbody>
            <?php foreach($siswa as $i => $s): ?>
            <tr>
              <td>A<?= $i+1 ?> — <?= htmlspecialchars($s['nama']) ?></td>
              <td class="num"><?= $s['jarak'] ?></td>
              <td class="num"><?= $s['nilai'] ?></td>
              <td class="num"><?= $s['prestasi'] ?></td>
              <td class="num"><?= $s['penghasilan'] ?></td>
              <td class="num"><?= $s['kip_kks'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- STEP 2 : PEMBAGI = sqrt(Σ xij²)                    -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="step-box">
      <div class="step-header" onclick="toggleStep(this)">
        <div class="step-badge">
          <span class="step-num">2</span>
          <div><div class="step-title">Pembagi — √(Σ x<sub>ij</sub>²) per Kriteria</div><div class="step-subtitle">Digunakan sebagai penyebut normalisasi vektor</div></div>
        </div>
        <svg class="step-chevron" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="step-body">
        <div class="tbl-wrap" style="padding:0 16px">
        <table>
          <thead><tr>
            <th>Keterangan</th><th>C1</th><th>C2</th><th>C3</th><th>C4</th><th>C5</th>
          </tr></thead>
          <tbody>
            <tr class="highlight-row">
              <td>Pembagi (√Σxij²)</td>
              <?php foreach($pembagi_display as $v): ?>
              <td class="num"><?= $v ?></td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- STEP 3 : MATRIX NORMALISASI R = xij / pembagi      -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="step-box">
      <div class="step-header" onclick="toggleStep(this)">
        <div class="step-badge">
          <span class="step-num">3</span>
          <div><div class="step-title">Matrix Normalisasi R</div><div class="step-subtitle">r<sub>ij</sub> = x<sub>ij</sub> / √(Σ x<sub>ij</sub>²)</div></div>
        </div>
        <svg class="step-chevron" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="step-body">
        <div class="tbl-wrap" style="padding:0 16px">
        <table>
          <thead><tr>
            <th>Alternatif</th><th>C1</th><th>C2</th><th>C3</th><th>C4</th><th>C5</th>
          </tr></thead>
          <tbody>
            <?php foreach($result as $i => $s): ?>
            <tr>
              <td>A<?= array_search($s, $result)+1 ?> — <?= htmlspecialchars($s['nama']) ?></td>
              <?php foreach($s['R'] as $v): ?><td class="num"><?= $v ?></td><?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- STEP 4 : MATRIX TERBOBOT Y = bobot × R             -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="step-box">
      <div class="step-header" onclick="toggleStep(this)">
        <div class="step-badge">
          <span class="step-num">4</span>
          <div><div class="step-title">Matrix Ternormalisasi Terbobot Y</div><div class="step-subtitle">y<sub>ij</sub> = w<sub>j</sub> × r<sub>ij</sub></div></div>
        </div>
        <svg class="step-chevron" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="step-body">
        <div class="tbl-wrap" style="padding:0 16px">
        <table>
          <thead><tr>
            <th>Alternatif</th><th>C1 (×0.25)</th><th>C2 (×0.25)</th><th>C3 (×0.15)</th><th>C4 (×0.20)</th><th>C5 (×0.15)</th>
          </tr></thead>
          <tbody>
            <?php foreach($result as $i => $s): ?>
            <tr>
              <td>A<?= $i+1 ?> — <?= htmlspecialchars($s['nama']) ?></td>
              <?php foreach($s['Y'] as $v): ?><td class="num"><?= $v ?></td><?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- STEP 5 : SOLUSI IDEAL POSITIF & NEGATIF            -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="step-box">
      <div class="step-header" onclick="toggleStep(this)">
        <div class="step-badge">
          <span class="step-num">5</span>
          <div><div class="step-title">Solusi Ideal Positif (A+) &amp; Negatif (A−)</div><div class="step-subtitle">A+ = MAX(Benefit) / MIN(Cost) &nbsp;|&nbsp; A− = MAX(Cost) / MIN(Benefit)</div></div>
        </div>
        <svg class="step-chevron" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="step-body">
        <div class="tbl-wrap" style="padding:0 16px">
        <table>
          <thead><tr>
            <th>Solusi</th><th>C1</th><th>C2</th><th>C3</th><th>C4</th><th>C5</th>
          </tr></thead>
          <tbody>
            <tr class="aplus-row">
              <td>A+ (Ideal Positif)</td>
              <?php foreach($Aplus_display as $v): ?><td class="num"><?= $v ?></td><?php endforeach; ?>
            </tr>
            <tr class="aminus-row">
              <td>A− (Ideal Negatif)</td>
              <?php foreach($Aminus_display as $v): ?><td class="num"><?= $v ?></td><?php endforeach; ?>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- STEP 6 : JARAK D+ dan D-                           -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="step-box">
      <div class="step-header" onclick="toggleStep(this)">
        <div class="step-badge">
          <span class="step-num">6</span>
          <div><div class="step-title">Jarak terhadap Solusi Ideal (D+ dan D−)</div><div class="step-subtitle">D+i = √Σ(A+j − yij)² &nbsp;|&nbsp; D−i = √Σ(yij − A−j)²</div></div>
        </div>
        <svg class="step-chevron" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="step-body">
        <div class="tbl-wrap" style="padding:0 16px">
        <table>
          <thead><tr>
            <th>Alternatif</th><th>D+ (ke Ideal Positif)</th><th>D− (ke Ideal Negatif)</th>
          </tr></thead>
          <tbody>
            <?php foreach($result as $i => $s): ?>
            <tr>
              <td>A<?= $i+1 ?> — <?= htmlspecialchars($s['nama']) ?></td>
              <td class="num"><?= $s['dplus'] ?></td>
              <td class="num"><?= $s['dminus'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- STEP 7 : NILAI PREFERENSI Ci + RANKING             -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="step-box">
      <div class="step-header open" onclick="toggleStep(this)">
        <div class="step-badge">
          <span class="step-num">7</span>
          <div><div class="step-title">Nilai Preferensi (Ci) &amp; Ranking Akhir</div><div class="step-subtitle">Ci = D−i / (D−i + D+i) — Semakin tinggi Ci semakin baik</div></div>
        </div>
        <svg class="step-chevron" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="step-body show">
        <div class="tbl-wrap" style="padding:0 16px">
        <table>
          <thead><tr>
            <th>Rank</th><th>Alternatif</th><th>D+</th><th>D−</th><th>Ci = D−/(D−+D+)</th><th>Keterangan</th>
          </tr></thead>
          <tbody>
            <?php foreach($result as $i => $s): $rank = $s['rank']; ?>
            <tr <?= $rank <= 3 ? 'class="highlight-row"' : '' ?>>
              <td>
                <?php
                  if($rank==1) echo '<span class="rank-badge rank-1">🥇</span>';
                  elseif($rank==2) echo '<span class="rank-badge rank-2">🥈</span>';
                  elseif($rank==3) echo '<span class="rank-badge rank-3">🥉</span>';
                  else echo '<span class="rank-badge rank-n">'.$rank.'</span>';
                ?>
              </td>
              <td><?= htmlspecialchars($s['nama']) ?></td>
              <td class="num"><?= $s['dplus'] ?></td>
              <td class="num"><?= $s['dminus'] ?></td>
              <td>
                <div class="score-bar-wrap">
                  <div class="score-bar-bg"><div class="score-bar-fill" style="width:<?= round($s['ci']*100) ?>%"></div></div>
                  <span class="score-val"><?= $s['ci'] ?></span>
                </div>
              </td>
              <td><?= $rank<=3 ? '<span class="badge-diterima">✓ Diterima</span>' : '<span class="badge-tidak">✗ Tidak</span>' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- Podium -->
    <?php if(count($result) >= 3): ?>
    <p class="section-title">Peringkat Teratas</p>
    <div class="podium">
      <div class="podium-item podium-2">
        <div class="podium-card">
          <div class="podium-rank">🥈</div>
          <div class="podium-name"><?= htmlspecialchars($result[1]['nama']) ?></div>
          <div class="podium-ci">Ci = <?= $result[1]['ci'] ?></div>
        </div>
      </div>
      <div class="podium-item podium-1">
        <div class="podium-card">
          <div class="podium-rank">🥇</div>
          <div class="podium-name"><?= htmlspecialchars($result[0]['nama']) ?></div>
          <div class="podium-ci">Ci = <?= $result[0]['ci'] ?></div>
        </div>
      </div>
      <div class="podium-item podium-3">
        <div class="podium-card">
          <div class="podium-rank">🥉</div>
          <div class="podium-name"><?= htmlspecialchars($result[2]['nama']) ?></div>
          <div class="podium-ci">Ci = <?= $result[2]['ci'] ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

<script>
function toggleDropdown() {
  const btn=document.getElementById('accountBtn');
  const menu=document.getElementById('dropdownMenu');
  btn.classList.toggle('open');
  menu.classList.toggle('show');
}
document.addEventListener('click',function(e){
  if(!document.getElementById('accountBtn').contains(e.target)){
    document.getElementById('accountBtn').classList.remove('open');
    document.getElementById('dropdownMenu').classList.remove('show');
  }
});

function toggleStep(header) {
  header.classList.toggle('open');
  const body = header.nextElementSibling;
  body.classList.toggle('show');
}
</script>
</body>
</html>
