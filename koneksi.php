<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'db_bukutamu';

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = @new mysqli($host, $user, $password, $database);
$db_connection_error = null;

if ($koneksi->connect_errno) {
    $db_connection_error = 'Koneksi ke database gagal. Pastikan MySQL aktif dan database db_bukutamu sudah diimpor.';
} else {
    $koneksi->set_charset('utf8mb4');
}
?>