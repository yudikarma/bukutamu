<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'koneksi.php';

$keyword = trim($_GET['q'] ?? '');
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$dataTamu = [];
$totalData = 0;
$totalHariIni = 0;
$totalInstansi = 0;
$previewMode = false;
$perPage = 5;
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int) ($_POST['id'] ?? 0);

    if ($db_connection_error !== null) {
        $_SESSION['error_message'] = $db_connection_error;
    } elseif ($deleteId <= 0) {
        $_SESSION['error_message'] = 'ID tamu tidak valid.';
    } else {
        $stmtDelete = $koneksi->prepare('DELETE FROM buku_tamu WHERE id = ?');

        if ($stmtDelete) {
            $stmtDelete->bind_param('i', $deleteId);
            $stmtDelete->execute();
            $affectedRows = $stmtDelete->affected_rows;
            $stmtDelete->close();

            if ($affectedRows > 0) {
                $_SESSION['success_message'] = 'Data tamu berhasil dihapus.';
            } else {
                $_SESSION['error_message'] = 'Data tamu tidak ditemukan atau sudah dihapus.';
            }
        } else {
            $_SESSION['error_message'] = 'Query hapus tidak dapat dijalankan.';
        }
    }

    $redirectUrl = 'daftar_tamu.php';
    $params = [];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($currentPage > 1) {
        $params['page'] = $currentPage;
    }
    if (!empty($params)) {
        $redirectUrl .= '?' . http_build_query($params);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

if ($db_connection_error === null) {
    $sql = 'SELECT id, nama, instansi, tujuan, tanggal, waktu FROM buku_tamu';

    if ($keyword !== '') {
        $sql .= ' WHERE nama LIKE ? OR instansi LIKE ?';
    }

    $sql .= ' ORDER BY tanggal DESC, waktu DESC, id DESC';
    $stmt = $koneksi->prepare($sql);

    if ($stmt) {
        if ($keyword !== '') {
            $searchTerm = '%' . $keyword . '%';
            $stmt->bind_param('ss', $searchTerm, $searchTerm);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dataTamu[] = $row;
        }
        $stmt->close();
    }

    $totalData = count($dataTamu);
    $today = date('Y-m-d');

    $resultToday = $koneksi->query("SELECT COUNT(*) AS total FROM buku_tamu WHERE tanggal = '{$today}'");
    if ($resultToday) {
        $totalHariIni = (int) ($resultToday->fetch_assoc()['total'] ?? 0);
    }

    $resultInstansi = $koneksi->query('SELECT COUNT(DISTINCT instansi) AS total FROM buku_tamu');
    if ($resultInstansi) {
        $totalInstansi = (int) ($resultInstansi->fetch_assoc()['total'] ?? 0);
    }
} else {
    $previewMode = true;
    $dataTamu = [
        [
            'id' => 1,
            'nama' => 'Budi Santoso',
            'instansi' => 'PT Maju Bersama',
            'tujuan' => 'Diskusi kerja sama akademik',
            'tanggal' => '2026-06-10',
            'waktu' => '08:30:00'
        ],
        [
            'id' => 2,
            'nama' => 'Siti Rahma',
            'instansi' => 'SMK Nusantara',
            'tujuan' => 'Kunjungan kampus',
            'tanggal' => '2026-06-10',
            'waktu' => '09:15:00'
        ],
        [
            'id' => 3,
            'nama' => 'Andi Pratama',
            'instansi' => 'Universitas Mandiri',
            'tujuan' => 'Studi banding sistem digital',
            'tanggal' => '2026-06-09',
            'waktu' => '13:45:00'
        ]
    ];

    if ($keyword !== '') {
        $dataTamu = array_values(array_filter($dataTamu, static function (array $tamu) use ($keyword): bool {
            $needle = strtolower($keyword);
            return str_contains(strtolower($tamu['nama']), $needle) || str_contains(strtolower($tamu['instansi']), $needle);
        }));
    }

    $totalData = count($dataTamu);
    $totalHariIni = 2;
    $totalInstansi = 3;
}

$totalRows = count($dataTamu);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;
$visibleTamu = array_slice($dataTamu, $offset, $perPage);

function buildPageUrl(int $page, string $keyword): string
{
    $params = ['page' => $page];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }

    return 'daftar_tamu.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tamu</title>
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
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="data-card p-4 p-lg-5 h-100">
                <p class="text-uppercase small fw-semibold text-primary mb-2">Dashboard data tamu</p>
                <h1 class="section-title h2 fw-bold mb-3">Tabel seluruh data kunjungan</h1>
                <p class="text-secondary mb-0">Halaman ini menampilkan seluruh data tamu dalam bentuk tabel Bootstrap dengan fitur pencarian berdasarkan nama atau instansi.</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="search-card search-card-highlight p-4 h-100">
                <form method="get" action="daftar_tamu.php" class="h-100 d-flex flex-column justify-content-between gap-3">
                    <div>
                        <div class="search-kicker mb-2">Fitur cepat</div>
                        <label for="q" class="form-label search-label">Pencarian Tamu</label>
                        <p class="search-helper mb-3">Cari data tamu berdasarkan nama atau instansi dari kotak pencarian ini.</p>
                        <input type="text" class="form-control search-input" id="q" name="q" placeholder="Cari nama atau instansi" value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-brand">Cari Data</button>
                        <a href="daftar_tamu.php" class="btn btn-outline-secondary">Reset Pencarian</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card p-4 h-100">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Total tampil</div>
                <div class="metric-value"><?php echo $totalData; ?></div>
                <div class="text-secondary">Data sesuai hasil pencarian saat ini</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card p-4 h-100">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Tamu hari ini</div>
                <div class="metric-value"><?php echo $totalHariIni; ?></div>
                <div class="text-secondary">Berdasarkan tanggal server hari ini</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card p-4 h-100">
                <div class="small text-uppercase text-secondary fw-semibold mb-2">Instansi unik</div>
                <div class="metric-value"><?php echo $totalInstansi; ?></div>
                <div class="text-secondary">Jumlah instansi berbeda yang tercatat</div>
            </div>
        </div>
    </div>

    <div class="data-card p-0 overflow-hidden">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-4 border-bottom">
            <div>
                <h2 class="h4 fw-bold mb-1">Daftar Buku Tamu</h2>
                <p class="text-secondary mb-0">Gunakan fitur cari untuk menyaring berdasarkan nama atau instansi.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-primary">Tambah Tamu</a>
            </div>
        </div>

        <?php if ($successMessage !== null): ?>
            <div class="p-4 pb-0">
                <div class="alert alert-success mb-0" role="alert"><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== null): ?>
            <div class="p-4 pb-0">
                <div class="alert alert-danger mb-0" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($previewMode): ?>
            <div class="p-4 pb-0">
                <div class="alert alert-warning mb-0" role="alert">
                    <?php echo htmlspecialchars($db_connection_error); ?> Tampilan tabel di bawah memakai data contoh agar layout tetap bisa dipreview.
                </div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Instansi</th>
                        <th>Tujuan</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($visibleTamu)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">Belum ada data tamu<?php echo $keyword !== '' ? ' yang sesuai pencarian.' : '.'; ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visibleTamu as $index => $tamu): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($tamu['nama']); ?></td>
                                <td><?php echo htmlspecialchars($tamu['instansi']); ?></td>
                                <td><span class="purpose-badge"><?php echo htmlspecialchars($tamu['tujuan']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($tamu['tanggal']))); ?></td>
                                <td><?php echo htmlspecialchars(substr($tamu['waktu'], 0, 5)); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if ($previewMode): ?>
                                            <span class="badge text-bg-secondary action-badge-disabled">Preview</span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo (int) $tamu['id']; ?>">Detail</button>
                                            <a href="edit_tamu.php?id=<?php echo (int) $tamu['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                data-delete-id="<?php echo (int) $tamu['id']; ?>"
                                                data-delete-name="<?php echo htmlspecialchars($tamu['nama'], ENT_QUOTES); ?>"
                                            >
                                                Hapus
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$previewMode && $totalRows > 0): ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 px-4 py-3 border-top bg-white">
                <div class="text-secondary small">
                    Menampilkan <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalRows); ?> dari <?php echo $totalRows; ?> data
                </div>
                <nav aria-label="Navigasi halaman tamu">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $currentPage <= 1 ? '#' : htmlspecialchars(buildPageUrl($currentPage - 1, $keyword)); ?>">Sebelumnya</a>
                        </li>
                        <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                            <li class="page-item <?php echo $page === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($page, $keyword)); ?>"><?php echo $page; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $currentPage >= $totalPages ? '#' : htmlspecialchars(buildPageUrl($currentPage + 1, $keyword)); ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php if (!$previewMode): ?>
    <?php foreach ($visibleTamu as $tamu): ?>
        <div class="modal fade" id="detailModal<?php echo (int) $tamu['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content detail-modal">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <p class="text-uppercase small fw-semibold text-primary mb-1">Detail tamu</p>
                            <h5 class="modal-title fw-bold mb-0"><?php echo htmlspecialchars($tamu['nama']); ?></h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="detail-list">
                            <div class="detail-item">
                                <span class="detail-label">Instansi</span>
                                <strong><?php echo htmlspecialchars($tamu['instansi']); ?></strong>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tujuan</span>
                                <strong><?php echo htmlspecialchars($tamu['tujuan']); ?></strong>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tanggal</span>
                                <strong><?php echo htmlspecialchars(date('d-m-Y', strtotime($tamu['tanggal']))); ?></strong>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Waktu</span>
                                <strong><?php echo htmlspecialchars(substr($tamu['waktu'], 0, 5)); ?> WIB</strong>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <a href="edit_tamu.php?id=<?php echo (int) $tamu['id']; ?>" class="btn btn-brand">Edit Data</a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content delete-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <p class="text-uppercase small fw-semibold text-danger mb-1">Konfirmasi hapus</p>
                        <h5 class="modal-title fw-bold mb-0">Hapus data tamu</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="mb-2">Data untuk <strong id="deleteGuestName">tamu ini</strong> akan dihapus dari database.</p>
                    <p class="text-secondary mb-0">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <form method="post" action="daftar_tamu.php<?php echo $keyword !== '' || $currentPage > 1 ? '?' . http_build_query(array_filter(['q' => $keyword, 'page' => $currentPage > 1 ? $currentPage : null])) : ''; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteGuestId" value="">
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<footer class="container pb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-top pt-4 footer-note">
        <span>Bootstrap lokal digunakan agar tampilan tetap konsisten saat offline.</span>
        <span>File ini siap diunggah ke GitHub tanpa template sumber.</span>
    </div>
</footer>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<?php if (!$previewMode): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('deleteModal');

    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }

            const guestId = trigger.getAttribute('data-delete-id');
            const guestName = trigger.getAttribute('data-delete-name');
            deleteModal.querySelector('#deleteGuestId').value = guestId || '';
            deleteModal.querySelector('#deleteGuestName').textContent = guestName || 'tamu ini';
        });
    }
});
</script>
<?php endif; ?>
</body>
</html>
