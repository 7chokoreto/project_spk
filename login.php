<?php include 'koneksi.php'; ?>
<?php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['login'] = true;
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — SPK Zonasi</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    --gray-400: #94a3b8;
    --gray-600: #475569;
    --gray-800: #1e293b;
  }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #bfdbfe 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }

  body::before {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%);
    top: -100px; left: -100px;
    animation: float 8s ease-in-out infinite;
  }

  body::after {
    content: '';
    position: absolute;
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
    bottom: -80px; right: -80px;
    animation: float 10s ease-in-out infinite reverse;
  }

  @keyframes float {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-30px) scale(1.05); }
  }

  .login-wrapper {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
    padding: 20px;
    animation: slideUp 0.6s cubic-bezier(0.16,1,0.3,1) both;
  }

  @keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .brand {
    text-align: center;
    margin-bottom: 32px;
  }

  .brand-icon {
    width: 56px; height: 56px;
    background: linear-gradient(135deg, var(--blue-500), var(--blue-700));
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    box-shadow: 0 8px 24px rgba(59,130,246,0.35);
  }

  .brand-icon svg { width: 28px; height: 28px; fill: white; }

  .brand h1 {
    font-size: 22px;
    font-weight: 700;
    color: var(--blue-900);
    letter-spacing: -0.5px;
  }

  .brand p {
    font-size: 13px;
    color: var(--gray-400);
    margin-top: 4px;
  }

  .card {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.9);
    border-radius: 24px;
    padding: 40px 36px;
    box-shadow: 0 20px 60px rgba(59,130,246,0.12), 0 4px 16px rgba(0,0,0,0.04);
  }

  .card h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 6px;
  }

  .card .subtitle {
    font-size: 13px;
    color: var(--gray-400);
    margin-bottom: 28px;
  }

  .error-msg {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    font-size: 13px;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .field {
    margin-bottom: 18px;
  }

  label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-600);
    margin-bottom: 7px;
  }

  .input-wrap {
    position: relative;
  }

  .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--blue-400);
    pointer-events: none;
  }

  input[type="text"], input[type="password"] {
    width: 100%;
    padding: 12px 14px 12px 42px;
    border: 1.5px solid var(--blue-100);
    border-radius: 12px;
    font-family: inherit;
    font-size: 14px;
    color: var(--gray-800);
    background: var(--white);
    transition: all 0.2s;
    outline: none;
  }

  input:focus {
    border-color: var(--blue-400);
    box-shadow: 0 0 0 4px rgba(96,165,250,0.15);
  }

  input::placeholder { color: #c0cde0; }

  .btn-login {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, var(--blue-500), var(--blue-700));
    color: white;
    font-family: inherit;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    margin-top: 8px;
    transition: all 0.2s;
    box-shadow: 0 4px 16px rgba(59,130,246,0.35);
    letter-spacing: 0.3px;
  }

  .btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(59,130,246,0.4);
  }

  .btn-login:active { transform: translateY(0); }

  .footer-note {
    text-align: center;
    font-size: 12px;
    color: var(--gray-400);
    margin-top: 24px;
  }
</style>
</head>
<body>
<div class="login-wrapper">
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
      </svg>
    </div>
    <h1>SPK Zonasi</h1>
    <p>Sistem Pendukung Keputusan Penerimaan Siswa</p>
  </div>
  <div class="card">
    <h2>Selamat Datang 👋</h2>
    <p class="subtitle">Masuk untuk mengakses panel admin</p>

    <?php if (!empty($error)): ?>
    <div class="error-msg">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= $error ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="field">
        <label>Username</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <input type="text" name="username" placeholder="Masukkan username" required>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input type="password" name="password" placeholder="Masukkan password" required>
        </div>
      </div>
      <button type="submit" name="login" class="btn-login">Masuk</button>
    </form>
  </div>
  <p class="footer-note">© 2025 SPK Zonasi — Sistem Pendukung Keputusan</p>
</div>
</body>
</html>