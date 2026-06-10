<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'koneksi.php';

$errors = [];
$old = [
    'nama' => '',
    'instansi' => '',
    'tujuan' => '',
    'tanggal' => date('Y-m-d'),
    'waktu' => date('H:i')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nama'] = trim($_POST['nama'] ?? '');
    $old['instansi'] = trim($_POST['instansi'] ?? '');
    $old['tujuan'] = trim($_POST['tujuan'] ?? '');
    $old['tanggal'] = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $old['waktu'] = trim($_POST['waktu'] ?? date('H:i'));

    if ($old['nama'] === '') {
        $errors[] = 'Nama wajib diisi.';
    }

    if ($old['instansi'] === '') {
        $errors[] = 'Instansi wajib diisi.';
    }

    if ($old['tujuan'] === '') {
        $errors[] = 'Tujuan kedatangan wajib diisi.';
    }

    $tanggalObj = DateTime::createFromFormat('Y-m-d', $old['tanggal']);
    if (!$tanggalObj || $tanggalObj->format('Y-m-d') !== $old['tanggal']) {
        $errors[] = 'Tanggal simpan tidak valid.';
    }

    $waktuObj = DateTime::createFromFormat('H:i', $old['waktu']);
    if (!$waktuObj || $waktuObj->format('H:i') !== $old['waktu']) {
        $errors[] = 'Waktu simpan tidak valid.';
    }

    if ($db_connection_error !== null) {
        $errors[] = $db_connection_error;
    }

    if (empty($errors)) {
        $tanggal = $old['tanggal'];
        $waktu = $old['waktu'] . ':00';

        $stmt = $koneksi->prepare('INSERT INTO buku_tamu (nama, instansi, tujuan, tanggal, waktu) VALUES (?, ?, ?, ?, ?)');

        if ($stmt) {
            $stmt->bind_param('sssss', $old['nama'], $old['instansi'], $old['tujuan'], $tanggal, $waktu);
            $isInserted = $stmt->execute();
            $stmt->close();

            if ($isInserted) {
                $_SESSION['success_message'] = 'Data tamu berhasil disimpan.';
                header('Location: index.php');
                exit;
            }

            $errors[] = 'Data gagal disimpan. Silakan coba lagi.';
        } else {
            $errors[] = 'Query insert tidak dapat dijalankan.';
        }
    }
}

$successMessage = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
$jumlahHariIni = 0;
$totalSemuaTamu = 0;

if ($db_connection_error === null) {
    $today = date('Y-m-d');
    $stmtCount = $koneksi->prepare('SELECT COUNT(*) AS total FROM buku_tamu WHERE tanggal = ?');
    if ($stmtCount) {
        $stmtCount->bind_param('s', $today);
        $stmtCount->execute();
        $resultCount = $stmtCount->get_result();
        $jumlahHariIni = (int) ($resultCount->fetch_assoc()['total'] ?? 0);
        $stmtCount->close();
    }

    $resultTotal = $koneksi->query('SELECT COUNT(*) AS total FROM buku_tamu');
    if ($resultTotal) {
        $totalSemuaTamu = (int) ($resultTotal->fetch_assoc()['total'] ?? 0);
    }
}

$currentDate = date('d F Y');
$currentTime = date('H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Digital Kampus</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--academic-navy);">
    <div class="container py-2">
        <a class="navbar-brand fw-bold" href="index.php">Buku Tamu Digital</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link active" href="index.php">Form Tamu</a></li>
                <li class="nav-item"><a class="nav-link" href="daftar_tamu.php">Daftar Tamu</a></li>
                <li class="nav-item mt-3 mt-lg-0"><a class="btn btn-accent rounded-pill px-4" href="daftar_tamu.php">Lihat Data</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-6">
            <section class="hero-panel h-100 p-4 p-lg-5 d-flex flex-column justify-content-between">
                <div>
                    <span class="hero-badge mb-4">Administrasi kampus modern dan offline-ready</span>
                    <h1 class="hero-title display-5 fw-bold mb-3">Selamat datang di aplikasi buku tamu kampus</h1>
                    <p class="lead text-white-50 mb-4">Isi data kunjungan dengan cepat, simpan langsung ke MySQL, dan kelola seluruh daftar tamu dalam satu dashboard sederhana.</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="info-chip h-100">
                            <div class="small text-uppercase text-info fw-semibold mb-2">Tanggal</div>
                            <div class="fs-5 fw-semibold"><?php echo htmlspecialchars($currentDate); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-chip h-100">
                            <div class="small text-uppercase text-warning fw-semibold mb-2">Waktu</div>
                            <div class="fs-5 fw-semibold"><?php echo htmlspecialchars($currentTime); ?> WIB</div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="info-chip h-100">
                            <div class="small text-uppercase text-white-50 fw-semibold mb-2">Statistik hari ini</div>
                            <div class="fs-5 fw-semibold"><?php echo $jumlahHariIni; ?> tamu tercatat pada tanggal <?php echo htmlspecialchars(date('d-m-Y')); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="hero-stat-panel">
                            <div class="hero-stat-item">
                                <span class="hero-stat-label">Total Seluruh Data</span>
                                <strong class="hero-stat-value"><?php echo $totalSemuaTamu; ?></strong>
                            </div>
                            <div class="hero-stat-divider"></div>
                            <div class="hero-stat-item">
                                <span class="hero-stat-label">Input Mengikuti Database</span>
                                <strong class="hero-stat-value">Live</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hero-insight-grid">
                    <div class="hero-insight-card">
                        <span class="hero-insight-step">01</span>
                        <h3 class="h5 mb-2">Isi data tamu</h3>
                        <p class="mb-0 text-white-50">Nama, instansi, tujuan, tanggal, dan waktu bisa dicatat langsung dari form ini.</p>
                    </div>
                    <div class="hero-insight-card">
                        <span class="hero-insight-step">02</span>
                        <h3 class="h5 mb-2">Simpan ke MySQL</h3>
                        <p class="mb-0 text-white-50">Data masuk ke tabel <code class="text-info">buku_tamu</code> memakai koneksi <code class="text-info">mysqli</code>.</p>
                    </div>
                    <div class="hero-insight-card hero-insight-accent">
                        <span class="hero-insight-step">03</span>
                        <h3 class="h5 mb-2">Pantau dari tabel</h3>
                        <p class="mb-0 text-white-50">Halaman daftar tamu menampilkan semua data lengkap dengan pencarian Bootstrap.</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="glass-card h-100 p-4 p-lg-5">
                <div class="mb-4">
                    <p class="text-uppercase small fw-semibold text-primary mb-2">Formulir kedatangan tamu</p>
                    <h2 class="card-title h3 fw-bold mb-2">Catat kunjungan baru</h2>
                    <p class="text-secondary mb-0">Tanggal dan waktu sudah terisi nilai sekarang, tetapi tetap bisa Anda ubah sebelum data disimpan.</p>
                </div>

                <?php if ($successMessage !== null): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($successMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-semibold mb-2">Perlu diperiksa:</div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($db_connection_error !== null): ?>
                    <div class="alert alert-warning" role="alert">
                        Aplikasi tetap bisa ditampilkan, tetapi penyimpanan data menunggu koneksi MySQL siap.
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php" class="row g-3">
                    <div class="col-12">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" placeholder="Contoh: Budi Santoso" value="<?php echo htmlspecialchars($old['nama']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="instansi" class="form-label">Instansi</label>
                        <input type="text" class="form-control" id="instansi" name="instansi" placeholder="Contoh: PT Maju Bersama" value="<?php echo htmlspecialchars($old['instansi']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="tujuan" class="form-label">Tujuan Kedatangan</label>
                        <textarea class="form-control" id="tujuan" name="tujuan" rows="4" placeholder="Tuliskan tujuan kunjungan" required><?php echo htmlspecialchars($old['tujuan']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal" class="form-label">Tanggal Simpan</label>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo htmlspecialchars($old['tanggal']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="waktu" class="form-label">Waktu Simpan</label>
                        <input type="time" class="form-control" id="waktu" name="waktu" value="<?php echo htmlspecialchars($old['waktu']); ?>" required>
                    </div>
                    <div class="col-12 d-grid d-md-flex gap-3 pt-2">
                        <button type="submit" class="btn btn-brand flex-grow-1">Simpan Data Tamu</button>
                        <a href="daftar_tamu.php" class="btn btn-outline-secondary flex-grow-1">Lihat Daftar Tamu</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</main>

<footer class="container pb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-top pt-4 footer-note">
        <span>Project Buku Tamu Digital berbasis PHP, MySQL, dan Bootstrap lokal.</span>
        <span>Siap dijalankan tanpa aset internet eksternal.</span>
    </div>
</footer>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
