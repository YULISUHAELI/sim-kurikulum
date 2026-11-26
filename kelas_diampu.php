<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $guru  = (int)$_POST['guru_id'];
    $mapel = (int)$_POST['mapel_id'];
    $kelas = (int)$_POST['kelas_id'];
    $jp    = (int)$_POST['jam_per_minggu'];
    mysqli_query($conn, "INSERT INTO kelas_diampu (guru_id, mapel_id, kelas_id, jam_per_minggu) VALUES ($guru, $mapel, $kelas, $jp)");
    header("Location: kelas_diampu.php");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM kelas_diampu WHERE id=".(int)$_GET['hapus']);
    header("Location: kelas_diampu.php");
    exit;
}
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Plotting Kelas yang Diampu Guru</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Tambah Penugasan Mengajar</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">Guru Pengampu</label>
                        <select name="guru_id" class="form-select" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php $g=mysqli_query($conn, "SELECT * FROM guru ORDER BY nama_guru"); while($rg=mysqli_fetch_assoc($g)): ?>
                                <option value="<?= $rg['id'] ?>"><?= htmlspecialchars($rg['nama_guru']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Mata Pelajaran</label>
                        <select name="mapel_id" class="form-select" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php $m=mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel"); while($rm=mysqli_fetch_assoc($m)): ?>
                                <option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['nama_mapel']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Kelas yang Diampu</label>
                        <select name="kelas_id" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php $k=mysqli_query($conn, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas"); while($rk=mysqli_fetch_assoc($k)): ?>
                                <option value="<?= $rk['id'] ?>"><?= htmlspecialchars($rk['nama_kelas']) ?> (Tingkat <?= $rk['tingkat'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Alokasi Jam Pelajaran (JP / Minggu)</label>
                        <input type="number" name="jam_per_minggu" class="form-control" value="4" min="1" max="12" required>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan Penugasan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Daftar Distribusi Kelas & Beban Mengajar</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nama Guru</th>
                            <th>Mata Pelajaran</th>
                            <th>Kelas Diampu</th>
                            <th>Jml Siswa</th>
                            <th>JP/Minggu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($conn, "
                            SELECT kd.*, g.nama_guru, m.nama_mapel, k.nama_kelas,
                            (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) as jml_siswa
                            FROM kelas_diampu kd
                            JOIN guru g ON kd.guru_id = g.id
                            JOIN mapel m ON kd.mapel_id = m.id
                            JOIN kelas k ON kd.kelas_id = k.id
                            ORDER BY g.nama_guru, k.nama_kelas
                        ");
                        while($r=mysqli_fetch_assoc($q)):
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($r['nama_guru']) ?></td>
                            <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                            <td><span class="badge bg-success"><?= htmlspecialchars($r['nama_kelas']) ?></span></td>
                            <td><?= $r['jml_siswa'] ?> Siswa</td>
                            <td><?= $r['jam_per_minggu'] ?> JP</td>
                            <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus penugasan?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
