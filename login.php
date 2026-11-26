<?php
require 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Pastikan tabel users memiliki akun admin default yang valid (mengatasi semua hash SQL lama v1-v7)
$broken_hashes = [
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1L9.g2/6C9pS.L2X6H/8m2xL.5f6e7u'
];
$cek_admin = mysqli_query($conn, "SELECT * FROM users WHERE username='admin' LIMIT 1");
if ($cek_admin && mysqli_num_rows($cek_admin) === 0) {
    $hash_default = password_hash('admin123', PASSWORD_BCRYPT);
    mysqli_query($conn, "INSERT INTO users (nama, username, password, role) VALUES ('Administrator Kurikulum', 'admin', '$hash_default', 'admin')");
} elseif ($cek_admin && ($adm = mysqli_fetch_assoc($cek_admin))) {
    if (in_array($adm['password'], $broken_hashes, true) || strlen($adm['password']) < 10) {
        $hash_baru = password_hash('admin123', PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE users SET password='$hash_baru' WHERE id=" . (int)$adm['id']);
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = esc($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $q = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' LIMIT 1");
    if ($q && ($row = mysqli_fetch_assoc($q))) {
        // Verifikasi menggunakan password_verify atau kecocokan langsung jika admin menginput plain text di DB
        if (password_verify($password, $row['password']) || $password === $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama']    = $row['nama'];
            $_SESSION['role']    = $row['role'];
            header("Location: index.php");
            exit;
        }
    }
    $error = "Username atau password yang Anda masukkan tidak sesuai.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem — SIM Kurikulum & e-Rapor SMP Fase D</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0284c7 100%);
            --accent-color: #2563eb;
            --accent-hover: #1d4ed8;
        }
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        body {
            min-height: 100vh;
            margin: 0;
            background-color: #f8fafc;
            display: flex;
            align-items: stretch;
        }
        .login-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        /* Panel Kiri - Branding & Ilustrasi Akademik */
        .brand-panel {
            flex: 1.15;
            background: var(--primary-grad);
            color: #ffffff;
            padding: 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .brand-panel::before {
            content: '';
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, rgba(56, 189, 248, 0) 70%);
            top: -120px;
            right: -120px;
            pointer-events: none;
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(99, 102, 241, 0) 70%);
            bottom: -90px;
            left: -80px;
            pointer-events: none;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            width: fit-content;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            padding: 1.25rem;
            transition: transform 0.25s ease;
        }
        .feature-card:hover {
            transform: translateY(-3px);
        }
        /* Panel Kanan - Form Login */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #ffffff;
        }
        .login-box {
            width: 100%;
            max-width: 420px;
        }
        .logo-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
        }
        .form-control-modern {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background-color: #f8fafc;
        }
        .form-control-modern:focus {
            background-color: #ffffff;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }
        .input-icon-wrapper {
            position: relative;
        }
        .input-icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
            z-index: 5;
        }
        .btn-toggle-pass {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            padding: 4px 8px;
            cursor: pointer;
            z-index: 5;
        }
        .btn-toggle-pass:hover {
            color: #0f172a;
        }
        .btn-login-modern {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            font-size: 0.98rem;
            letter-spacing: 0.2px;
            box-shadow: 0 10px 18px -6px rgba(37, 99, 235, 0.45);
            transition: all 0.2s ease;
        }
        .btn-login-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 22px -6px rgba(37, 99, 235, 0.55);
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        }
        .demo-credential-box {
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.84rem;
            color: #475569;
        }
        @media (max-width: 991.98px) {
            .brand-panel {
                display: none;
            }
            .form-panel {
                background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
            }
            .login-box {
                background: #ffffff;
                padding: 2rem;
                border-radius: 20px;
                box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.08);
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Left Branding Panel -->
    <div class="brand-panel">
        <div>
            <div class="brand-badge mb-4">
                <i class="bi bi-stars text-info"></i>
                <span>Kurikulum Merdeka — Jenjang SMP (Fase D)</span>
            </div>
            <h1 class="display-6 fw-bold mb-3" style="line-height: 1.25;">
                Sistem Informasi Manajemen Kurikulum & e-Rapor Digital
            </h1>
            <p class="text-white-50 fs-6 mb-5" style="max-width: 520px;">
                Kelola Capaian Pembelajaran (CP), Alur Tujuan Pembelajaran (ATP), Modul Ajar, Rekap Nilai, Leger Kelas, hingga cetak e-Rapor dalam satu platform terpadu.
            </p>

            <div class="row g-3" style="max-width: 540px;">
                <div class="col-6">
                    <div class="feature-card">
                        <i class="bi bi-journal-check fs-4 text-info mb-2 d-block"></i>
                        <div class="fw-bold small mb-1">Perangkat Ajar Fase D</div>
                        <div class="text-white-50" style="font-size: 0.78rem;">Penyusunan CP, TP, ATP & cetak Modul Ajar otomatis.</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="feature-card">
                        <i class="bi bi-award fs-4 text-warning mb-2 d-block"></i>
                        <div class="fw-bold small mb-1">e-Rapor & Leger Kelas</div>
                        <div class="text-white-50" style="font-size: 0.78rem;">Deskripsi capaian berbasis TP & cetak rapor resmi.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center text-white-50 small pt-4 border-top border-white border-opacity-10">
            <span>&copy; 2026 SIM Kurikulum SMP Fase D</span>
            <span>Versi 11.0 (Import Siswa & Leger F4)</span>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="form-panel">
        <div class="login-box">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="logo-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-0">Selamat Datang</h4>
                    <p class="text-muted small mb-0">Silakan masuk ke akun pengelola kurikulum</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center gap-2 py-2.5 px-3 mb-4" role="alert" style="background-color: #fef2f2; color: #b91c1c; font-size: 0.88rem;">
                    <i class="bi bi-exclamation-circle-fill fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Username</label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-person input-icon-left"></i>
                        <input type="text" name="username" id="username" class="form-control form-control-modern" placeholder="Masukkan username Anda" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label small fw-semibold text-secondary">Password</label>
                    </div>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-lock input-icon-left"></i>
                        <input type="password" name="password" id="password" class="form-control form-control-modern pe-5" placeholder="Masukkan password Anda" value="admin123" required>
                        <button type="button" class="btn-toggle-pass" onclick="togglePassword()" title="Tampilkan/Sembunyikan Password">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login-modern w-100 text-white mb-4">
                    <span>Masuk ke Dashboard</span>
                    <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>

            <div class="demo-credential-box d-flex align-items-center justify-content-between">
                <div>
                    <div class="fw-semibold text-dark mb-0.5"><i class="bi bi-key-fill text-primary me-1"></i>Akses Default Sistem:</div>
                    <span>User: <code class="text-primary fw-bold">admin</code> &bull; Pass: <code class="text-primary fw-bold">admin123</code></span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="fillDefault()">Isi Otomatis</button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    if (passInput.type === 'password') {
        passInput.type = 'text';
        eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passInput.type = 'password';
        eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
function fillDefault() {
    document.getElementById('username').value = 'admin';
    document.getElementById('password').value = 'admin123';
}
</script>
</body>
</html>
