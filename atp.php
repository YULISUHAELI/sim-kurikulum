<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $tp_id  = (int)$_POST['tp_id'];
    $urutan = (int)$_POST['urutan'];
    $jp     = (int)$_POST['alokasi_jp'];
    $p5     = esc($_POST['profil_pancasila']);
    $kata   = esc($_POST['kata_kunci']);
    mysqli_query($conn, "INSERT INTO alur_tujuan_pembelajaran (tp_id, urutan, alokasi_jp, profil_pancasila, kata_kunci) VALUES ($tp_id, $urutan, $jp, '$p5', '$kata')");
    header("Location: atp.php");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM alur_tujuan_pembelajaran WHERE id=".(int)$_GET['hapus']);
    header("Location: atp.php");
    exit;
}
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Alur Tujuan Pembelajaran (ATP)</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Susun Alur (ATP)</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">Pilih Tujuan Pembelajaran (TP)</label>
                        <select name="tp_id" class="form-select" required>
                            <option value="">-- Pilih TP --</option>
                            <?php $tp=mysqli_query($conn, "SELECT tp.id, tp.kode_tp, m.nama_mapel FROM tujuan_pembelajaran tp JOIN capaian_pembelajaran cp ON tp.cp_id=cp.id JOIN mapel m ON cp.mapel_id=m.id"); while($rt=mysqli_fetch_assoc($tp)): ?>
                                <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['kode_tp'].' ('.$rt['nama_mapel'].')') ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Urutan Alur</label>
                            <input type="number" name="urutan" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Alokasi Waktu (JP)</label>
                            <input type="number" name="alokasi_jp" class="form-control" value="6" min="1" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Dimensi Profil Pelajar Pancasila</label>
                        <input type="text" name="profil_pancasila" class="form-control" placeholder="Bernalar Kritis, Mandiri" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Kata Kunci / Topik Materi</label>
                        <input type="text" name="kata_kunci" class="form-control" placeholder="Bilangan Bulat, Garis Bilangan" required>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Tambahkan ke ATP</button>
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
                            <th>Urutan</th>
                            <th>Kode TP & Mapel</th>
                            <th>Deskripsi TP</th>
                            <th>JP</th>
                            <th>Profil Pancasila & Kata Kunci</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($conn, "SELECT a.*, tp.kode_tp, tp.deskripsi_tp, m.nama_mapel FROM alur_tujuan_pembelajaran a JOIN tujuan_pembelajaran tp ON a.tp_id=tp.id JOIN capaian_pembelajaran cp ON tp.cp_id=cp.id JOIN mapel m ON cp.mapel_id=m.id ORDER BY m.nama_mapel, a.urutan");
                        while($r=mysqli_fetch_assoc($q)):
                        ?>
                        <tr>
                            <td class="text-center fw-bold">#<?= $r['urutan'] ?></td>
                            <td class="small"><strong><?= htmlspecialchars($r['kode_tp']) ?></strong><br><?= htmlspecialchars($r['nama_mapel']) ?></td>
                            <td class="small"><?= htmlspecialchars($r['deskripsi_tp']) ?></td>
                            <td><span class="badge bg-warning text-dark"><?= $r['alokasi_jp'] ?> JP</span></td>
                            <td class="small"><strong>P5:</strong> <?= htmlspecialchars($r['profil_pancasila']) ?><br><strong>Topik:</strong> <?= htmlspecialchars($r['kata_kunci']) ?></td>
                            <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus ATP?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
