<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $kode = esc($_POST['kode_mapel']);
    $nama = esc($_POST['nama_mapel']);
    $kel  = esc($_POST['kelompok']);
    mysqli_query($conn, "INSERT INTO mapel (kode_mapel, nama_mapel, kelompok) VALUES ('$kode', '$nama', '$kel')");
    header("Location: mapel.php");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM mapel WHERE id=".(int)$_GET['hapus']);
    header("Location: mapel.php");
    exit;
}
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Manajemen Mata Pelajaran (Fase D)</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Tambah Mata Pelajaran</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">Kode Mapel</label>
                        <input type="text" name="kode_mapel" class="form-control" placeholder="Contoh: IPA" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nama Mata Pelajaran</label>
                        <input type="text" name="nama_mapel" class="form-control" placeholder="Contoh: Ilmu Pengetahuan Alam" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Kelompok</label>
                        <select name="kelompok" class="form-select">
                            <option value="Umum">Mata Pelajaran Umum</option>
                            <option value="Pilihan">Mata Pelajaran Pilihan</option>
                            <option value="Muatan Lokal">Muatan Lokal</option>
                            <option value="P5">Projek Penguatan Profil Pelajar Pancasila (P5)</option>
                        </select>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan Mapel</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Mata Pelajaran</th>
                            <th>Kelompok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $q=mysqli_query($conn, "SELECT * FROM mapel ORDER BY kode_mapel"); while($r=mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($r['kode_mapel']) ?></span></td>
                            <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                            <td><?= htmlspecialchars($r['kelompok']) ?></td>
                            <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus mapel?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
