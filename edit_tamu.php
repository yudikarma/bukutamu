<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'koneksi.php';

if ($db_connection_error !== null) {
    $_SESSION['error_message'] = $db_connection_error;
    header('Location: daftar_tamu.php');
    exit;
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error_message'] = 'ID tamu tidak valid.';
    header('Location: daftar_tamu.php');
    exit;
}

$errors = [];
$old = [
    'nama' => '',
    'instansi' => '',
    'tujuan' => '',
    'tanggal' => date('Y-m-d'),
    'waktu' => date('H:i')
];

$stmtFind = $koneksi->prepare('SELECT id, nama, instansi, tujuan, tanggal, waktu FROM buku_tamu WHERE id = ?');
if (!$stmtFind) {
    $_SESSION['error_message'] = 'Query data tamu tidak dapat dijalankan.';
    header('Location: daftar_tamu.php');
    exit;
}

$stmtFind->bind_param('i', $id);
$stmtFind->execute();
$resultFind = $stmtFind->get_result();
$tamu = $resultFind->fetch_assoc();
$stmtFind->close();

if (!$tamu) {
    $_SESSION['error_message'] = 'Data tamu tidak ditemukan.';
    header('Location: daftar_tamu.php');
    exit;
}

$old = [
    'nama' => $tamu['nama'],
    'instansi' => $tamu['instansi'],
    'tujuan' => $tamu['tujuan'],
    'tanggal' => $tamu['tanggal'],
    'waktu' => substr($tamu['waktu'], 0, 5)
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

    if (empty($errors)) {
        $waktuSimpan = $old['waktu'] . ':00';
        $stmtUpdate = $koneksi->prepare('UPDATE buku_tamu SET nama = ?, instansi = ?, tujuan = ?, tanggal = ?, waktu = ? WHERE id = ?');

        if ($stmtUpdate) {
            $stmtUpdate->bind_param('sssssi', $old['nama'], $old['instansi'], $old['tujuan'], $old['tanggal'], $waktuSimpan, $id);
            $isUpdated = $stmtUpdate->execute();
            $stmtUpdate->close();

            if ($isUpdated) {
                $_SESSION['success_message'] = 'Data tamu berhasil diperbarui.';
                header('Location: daftar_tamu.php');
                exit;
            }

            $errors[] = 'Data tamu gagal diperbarui.';
        } else {
            $errors[] = 'Query update tidak dapat dijalankan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Tamu</title>
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
                <li class="nav-item"><a class="nav-link" href="index.php">Form Tamu</a></li>
                <li class="nav-item"><a class="nav-link active" href="daftar_tamu.php">Daftar Tamu</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-9">
            <section class="glass-card p-4 p-lg-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                    <div>
                        <p class="text-uppercase small fw-semibold text-primary mb-2">Kelola data tamu</p>
                        <h1 class="card-title h2 fw-bold mb-2">Edit data kunjungan</h1>
                        <p class="text-secondary mb-0">Perbarui informasi tamu lalu simpan perubahan ke database.</p>
                    </div>
                    <a href="daftar_tamu.php" class="btn btn-outline-secondary">Kembali ke Daftar</a>
                </div>

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

                <form method="post" action="edit_tamu.php?id=<?php echo $id; ?>" class="row g-3">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="col-12">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($old['nama']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="instansi" class="form-label">Instansi</label>
                        <input type="text" class="form-control" id="instansi" name="instansi" value="<?php echo htmlspecialchars($old['instansi']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="tujuan" class="form-label">Tujuan Kedatangan</label>
                        <textarea class="form-control" id="tujuan" name="tujuan" rows="4" required><?php echo htmlspecialchars($old['tujuan']); ?></textarea>
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
                        <button type="submit" class="btn btn-brand flex-grow-1">Simpan Perubahan</button>
                        <a href="daftar_tamu.php" class="btn btn-outline-secondary flex-grow-1">Batal</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</main>

<footer class="container pb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-top pt-4 footer-note">
        <span>Edit data tamu langsung dari database MySQL.</span>
        <span>Perubahan akan tampil kembali di halaman daftar tamu.</span>
    </div>
</footer>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>