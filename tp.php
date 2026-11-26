<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $cp_id   = (int)$_POST['cp_id'];
    $kode_tp = esc($_POST['kode_tp']);
    $desk_tp = esc($_POST['deskripsi_tp']);
    $kelas   = esc($_POST['kelas_target']);
    $sem     = esc($_POST['semester']);
    mysqli_query($conn, "INSERT INTO tujuan_pembelajaran (cp_id, kode_tp, deskripsi_tp, kelas_target, semester) VALUES ($cp_id, '$kode_tp', '$desk_tp', '$kelas', '$sem')");
    header("Location: tp.php");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM tujuan_pembelajaran WHERE id=".(int)$_GET['hapus']);
    header("Location: tp.php");
    exit;
}
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Tujuan Pembelajaran (TP)</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Rumuskan Tujuan Pembelajaran</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">Referensi CP & Elemen</label>
                        <select name="cp_id" class="form-select" required>
                            <option value="">-- Pilih CP --</option>
                            <?php $cp=mysqli_query($conn, "SELECT cp.id, m.nama_mapel, cp.elemen FROM capaian_pembelajaran cp JOIN mapel m ON cp.mapel_id=m.id"); while($rc=mysqli_fetch_assoc($cp)): ?>
                                <option value="<?= $rc['id'] ?>"><?= htmlspecialchars($rc['nama_mapel'].' - '.$rc['elemen']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Kode TP</label>
                        <input type="text" name="kode_tp" class="form-control" placeholder="Contoh: MTK.D.7.2" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Target Kelas</label>
                            <select name="kelas_target" class="form-select">
                                <option value="VII">Kelas VII</option>
                                <option value="VIII">Kelas VIII</option>
                                <option value="IX">Kelas IX</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="1">Ganjil (1)</option>
                                <option value="2">Genap (2)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Deskripsi Tujuan Pembelajaran</label>
                        <textarea name="deskripsi_tp" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan TP</button>
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
                            <th>Kode TP</th>
                            <th>Mapel / Elemen</th>
                            <th>Kelas / Sem</th>
                            <th>Deskripsi TP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($conn, "SELECT tp.*, cp.elemen, m.nama_mapel FROM tujuan_pembelajaran tp JOIN capaian_pembelajaran cp ON tp.cp_id=cp.id JOIN mapel m ON cp.mapel_id=m.id ORDER BY tp.kode_tp");
                        while($r=mysqli_fetch_assoc($q)):
                        ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($r['kode_tp']) ?></span></td>
                            <td class="small"><strong><?= htmlspecialchars($r['nama_mapel']) ?></strong><br><?= htmlspecialchars($r['elemen']) ?></td>
                            <td class="small">Kls <?= $r['kelas_target'] ?> / Sem <?= $r['semester'] ?></td>
                            <td class="small"><?= nl2br(htmlspecialchars($r['deskripsi_tp'])) ?></td>
                            <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus TP?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
