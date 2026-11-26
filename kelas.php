<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $nama   = esc($_POST['nama_kelas']);
    $tingkat= esc($_POST['tingkat']);
    $ta     = esc($_POST['tahun_ajaran']);
    $wali   = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : "NULL";
    mysqli_query($conn, "INSERT INTO kelas (nama_kelas, tingkat, tahun_ajaran, wali_kelas_id) VALUES ('$nama', '$tingkat', '$ta', $wali)");
    header("Location: kelas.php");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM kelas WHERE id=".(int)$_GET['hapus']);
    header("Location: kelas.php");
    exit;
}
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Manajemen Kelas (Fase D: Tingkat VII, VIII, IX)</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Tambah Rombongan Belajar</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: VII-B" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Tingkat (Fase D)</label>
                        <select name="tingkat" class="form-select" required>
                            <option value="VII">Kelas VII</option>
                            <option value="VIII">Kelas VIII</option>
                            <option value="IX">Kelas IX</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" value="2026/2027" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Wali Kelas</label>
                        <select name="wali_kelas_id" class="form-select">
                            <option value="">-- Pilih Wali Kelas --</option>
                            <?php $g=mysqli_query($conn, "SELECT * FROM guru ORDER BY nama_guru"); while($rg=mysqli_fetch_assoc($g)): ?>
                                <option value="<?= $rg['id'] ?>"><?= htmlspecialchars($rg['nama_guru']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan Kelas</button>
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
                            <th>Kelas</th>
                            <th>Tingkat</th>
                            <th>Tahun Ajaran</th>
                            <th>Wali Kelas</th>
                            <th>Jumlah Siswa</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q=mysqli_query($conn, "SELECT k.*, g.nama_guru, (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id=k.id) as total_siswa FROM kelas k LEFT JOIN guru g ON k.wali_kelas_id=g.id ORDER BY k.tingkat, k.nama_kelas");
                        while($r=mysqli_fetch_assoc($q)):
                        ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($r['nama_kelas']) ?></td>
                            <td>Kelas <?= $r['tingkat'] ?></td>
                            <td><?= htmlspecialchars($r['tahun_ajaran']) ?></td>
                            <td><?= htmlspecialchars($r['nama_guru'] ?? '-') ?></td>
                            <td><span class="badge bg-info text-dark"><?= $r['total_siswa'] ?> Siswa</span></td>
                            <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus kelas?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
