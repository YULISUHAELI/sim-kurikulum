<?php
require 'config/database.php';
cek_login();

$jml_guru  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM guru"))[0];
$jml_siswa = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM siswa"))[0];
$jml_kelas = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM kelas"))[0];
$jml_mapel = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM mapel"))[0];
$jml_cp    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM capaian_pembelajaran"))[0];
$jml_tp    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tujuan_pembelajaran"))[0];
$jml_atp   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM alur_tujuan_pembelajaran"))[0];
$jml_modul = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM modul_ajar"))[0];

include 'includes/header.php';
?>
<h3 class="fw-bold mb-4">Dashboard SIM Kurikulum SMP (Fase D)</h3>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-stat bg-primary text-white p-3">
            <div class="small">Total Guru</div>
            <h2 class="fw-bold mb-0"><?= $jml_guru ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat bg-success text-white p-3">
            <div class="small">Total Siswa Aktif</div>
            <h2 class="fw-bold mb-0"><?= $jml_siswa ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat bg-info text-white p-3">
            <div class="small">Rombongan Belajar (Kelas)</div>
            <h2 class="fw-bold mb-0"><?= $jml_kelas ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat bg-dark text-white p-3">
            <div class="small">Mata Pelajaran</div>
            <h2 class="fw-bold mb-0"><?= $jml_mapel ?></h2>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-stat p-3 border-start border-4 border-primary">
            <div class="text-muted small">Capaian Pembelajaran (CP)</div>
            <h3 class="fw-bold mb-0"><?= $jml_cp ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3 border-start border-4 border-success">
            <div class="text-muted small">Tujuan Pembelajaran (TP)</div>
            <h3 class="fw-bold mb-0"><?= $jml_tp ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3 border-start border-4 border-warning">
            <div class="text-muted small">Alur Tujuan Pembelajaran (ATP)</div>
            <h3 class="fw-bold mb-0"><?= $jml_atp ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3 border-start border-4 border-danger">
            <div class="text-muted small">Modul Ajar Tersusun</div>
            <h3 class="fw-bold mb-0"><?= $jml_modul ?></h3>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold">Ringkasan Distribusi Kelas yang Diampu</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Guru Pengampu</th>
                    <th>Mata Pelajaran</th>
                    <th>Kelas</th>
                    <th>Tingkat</th>
                    <th>Jumlah Siswa</th>
                    <th>Beban JP/Minggu</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = mysqli_query($conn, "
                    SELECT kd.*, g.nama_guru, m.nama_mapel, k.nama_kelas, k.tingkat,
                    (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) as jml_siswa
                    FROM kelas_diampu kd
                    JOIN guru g ON kd.guru_id = g.id
                    JOIN mapel m ON kd.mapel_id = m.id
                    JOIN kelas k ON kd.kelas_id = k.id
                    ORDER BY g.nama_guru, k.nama_kelas
                ");
                while ($r = mysqli_fetch_assoc($q)):
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['nama_guru']) ?></td>
                    <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['nama_kelas']) ?></span></td>
                    <td>Kelas <?= $r['tingkat'] ?></td>
                    <td><?= $r['jml_siswa'] ?> Siswa</td>
                    <td><?= $r['jam_per_minggu'] ?> JP</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
