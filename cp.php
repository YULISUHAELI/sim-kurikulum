<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $mapel  = (int)$_POST['mapel_id'];
    $elemen = esc($_POST['elemen']);
    $desk   = esc($_POST['deskripsi_cp']);
    mysqli_query($conn, "INSERT INTO capaian_pembelajaran (mapel_id, fase, elemen, deskripsi_cp) VALUES ($mapel, 'D', '$elemen', '$desk')");
    header("Location: cp.php");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM capaian_pembelajaran WHERE id=".(int)$_GET['hapus']);
    header("Location: cp.php");
    exit;
}
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Capaian Pembelajaran (CP) — Fase D</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Input Capaian Pembelajaran</div>
            <div class="card-body">
                <form method="POST">
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
                        <label class="form-label small">Elemen CP</label>
                        <input type="text" name="elemen" class="form-control" placeholder="Contoh: Bilangan / Pemahaman IPA" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Deskripsi Capaian Pembelajaran (Fase D)</label>
                        <textarea name="deskripsi_cp" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan CP</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Mapel</th>
                            <th>Elemen</th>
                            <th>Deskripsi CP (Fase D)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $q=mysqli_query($conn, "SELECT cp.*, m.nama_mapel FROM capaian_pembelajaran cp JOIN mapel m ON cp.mapel_id=m.id ORDER BY m.nama_mapel"); while($r=mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($r['nama_mapel']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['elemen']) ?></span></td>
                            <td class="small"><?= nl2br(htmlspecialchars($r['deskripsi_cp'])) ?></td>
                            <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus CP?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
