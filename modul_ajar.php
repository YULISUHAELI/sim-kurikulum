<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $guru   = (int)$_POST['guru_id'];
    $mapel  = (int)$_POST['mapel_id'];
    $tp     = !empty($_POST['tp_id']) ? (int)$_POST['tp_id'] : "NULL";
    $kelas  = esc($_POST['kelas_tingkat']);
    $judul  = esc($_POST['judul_modul']);
    $waktu  = esc($_POST['alokasi_waktu']);
    $awal   = esc($_POST['kompetensi_awal']);
    $sarana = esc($_POST['sarana_prasarana']);
    $model  = esc($_POST['model_pembelajaran']);
    $keg    = esc($_POST['kegiatan_pembelajaran']);
    $asesmen= esc($_POST['asesmen']);

    mysqli_query($conn, "INSERT INTO modul_ajar (guru_id, mapel_id, tp_id, kelas_tingkat, judul_modul, alokasi_waktu, kompetensi_awal, sarana_prasarana, model_pembelajaran, kegiatan_pembelajaran, asesmen) VALUES ($guru, $mapel, $tp, '$kelas', '$judul', '$waktu', '$awal', '$sarana', '$model', '$keg', '$asesmen')");
    header("Location: modul_ajar.php");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM modul_ajar WHERE id=".(int)$_GET['hapus']);
    header("Location: modul_ajar.php");
    exit;
}

if (isset($_GET['cetak'])) {
    $id = (int)$_GET['cetak'];
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ma.*, g.nama_guru, g.nip, m.nama_mapel, tp.kode_tp, tp.deskripsi_tp FROM modul_ajar ma JOIN guru g ON ma.guru_id=g.id JOIN mapel m ON ma.mapel_id=m.id LEFT JOIN tujuan_pembelajaran tp ON ma.tp_id=tp.id WHERE ma.id=$id"));
    if ($r):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Modul Ajar - <?= htmlspecialchars($r['judul_modul']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none; } }</style>
</head>
<body class="p-4">
    <div class="container">
        <div class="mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF</button>
            <a href="modul_ajar.php" class="btn btn-secondary">Kembali</a>
        </div>
        <h4 class="text-center fw-bold text-uppercase mb-1">Modul Ajar Kurikulum Merdeka (Fase D)</h4>
        <p class="text-center text-muted mb-4">Jenjang SMP — Kelas <?= $r['kelas_tingkat'] ?></p>

        <table class="table table-bordered">
            <tr><th width="25%">Judul Modul</th><td><?= htmlspecialchars($r['judul_modul']) ?></td></tr>
            <tr><th>Mata Pelajaran</th><td><?= htmlspecialchars($r['nama_mapel']) ?></td></tr>
            <tr><th>Penyusun</th><td><?= htmlspecialchars($r['nama_guru']) ?> (NIP: <?= htmlspecialchars($r['nip']) ?>)</td></tr>
            <tr><th>Alokasi Waktu</th><td><?= htmlspecialchars($r['alokasi_waktu']) ?></td></tr>
            <tr><th>Model Pembelajaran</th><td><?= htmlspecialchars($r['model_pembelajaran']) ?></td></tr>
            <tr><th>Tujuan Pembelajaran</th><td><strong><?= htmlspecialchars($r['kode_tp'] ?? '-') ?></strong>: <?= htmlspecialchars($r['deskripsi_tp'] ?? '-') ?></td></tr>
            <tr><th>Kompetensi Awal</th><td><?= nl2br(htmlspecialchars($r['kompetensi_awal'])) ?></td></tr>
            <tr><th>Sarana & Prasarana</th><td><?= nl2br(htmlspecialchars($r['sarana_prasarana'])) ?></td></tr>
            <tr><th>Kegiatan Pembelajaran</th><td><?= nl2br(htmlspecialchars($r['kegiatan_pembelajaran'])) ?></td></tr>
            <tr><th>Asesmen / Penilaian</th><td><?= nl2br(htmlspecialchars($r['asesmen'])) ?></td></tr>
        </table>
    </div>
</body>
</html>
<?php
    exit;
    endif;
}

include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Penyusunan & Arsip Modul Ajar</h4>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Buat Modul Ajar Baru</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3 mb-2">
                <div class="col-md-4">
                    <label class="form-label small">Guru Penyusun</label>
                    <select name="guru_id" class="form-select" required>
                        <?php $g=mysqli_query($conn, "SELECT * FROM guru ORDER BY nama_guru"); while($rg=mysqli_fetch_assoc($g)): ?>
                            <option value="<?= $rg['id'] ?>"><?= htmlspecialchars($rg['nama_guru']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Mata Pelajaran</label>
                    <select name="mapel_id" class="form-select" required>
                        <?php $m=mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel"); while($rm=mysqli_fetch_assoc($m)): ?>
                            <option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['nama_mapel']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Tujuan Pembelajaran (TP)</label>
                    <select name="tp_id" class="form-select">
                        <option value="">-- Pilih TP --</option>
                        <?php $tp=mysqli_query($conn, "SELECT id, kode_tp FROM tujuan_pembelajaran"); while($rt=mysqli_fetch_assoc($tp)): ?>
                            <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['kode_tp']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-2">
                <div class="col-md-5">
                    <label class="form-label small">Judul / Topik Modul Ajar</label>
                    <input type="text" name="judul_modul" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tingkat Kelas</label>
                    <select name="kelas_tingkat" class="form-select">
                        <option value="VII">Kelas VII</option>
                        <option value="VIII">Kelas VIII</option>
                        <option value="IX">Kelas IX</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Alokasi Waktu</label>
                    <input type="text" name="alokasi_waktu" class="form-control" placeholder="2 x 40 Menit" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Model Pembelajaran</label>
                    <input type="text" name="model_pembelajaran" class="form-control" placeholder="Problem Based Learning">
                </div>
            </div>
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label class="form-label small">Kompetensi Awal</label>
                    <textarea name="kompetensi_awal" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Sarana dan Prasarana</label>
                    <textarea name="sarana_prasarana" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small">Langkah Kegiatan Pembelajaran (Pendahuluan, Inti, Penutup)</label>
                    <textarea name="kegiatan_pembelajaran" class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Rencana Asesmen (Diagnostik, Formatif, Sumatif)</label>
                    <textarea name="asesmen" class="form-control" rows="4" required></textarea>
                </div>
            </div>
            <button type="submit" name="simpan" class="btn btn-primary">Simpan Modul Ajar</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Daftar Modul Ajar Tersimpan</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th>Judul Modul</th>
                    <th>Mapel & Kelas</th>
                    <th>Guru Penyusun</th>
                    <th>Alokasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $q=mysqli_query($conn, "SELECT ma.*, g.nama_guru, m.nama_mapel FROM modul_ajar ma JOIN guru g ON ma.guru_id=g.id JOIN mapel m ON ma.mapel_id=m.id ORDER BY ma.id DESC"); while($r=mysqli_fetch_assoc($q)): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($r['judul_modul']) ?></td>
                    <td><?= htmlspecialchars($r['nama_mapel']) ?> (Kls <?= $r['kelas_tingkat'] ?>)</td>
                    <td><?= htmlspecialchars($r['nama_guru']) ?></td>
                    <td><?= htmlspecialchars($r['alokasi_waktu']) ?></td>
                    <td>
                        <a href="?cetak=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Cetak</a>
                        <a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus modul?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
