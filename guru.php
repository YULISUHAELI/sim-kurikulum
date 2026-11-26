<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $nip   = esc($_POST['nip']);
    $nama  = esc($_POST['nama_guru']);
    $email = esc($_POST['email']);
    $hp    = esc($_POST['no_hp']);
    mysqli_query($conn, "INSERT INTO guru (nip, nama_guru, email, no_hp) VALUES ('$nip', '$nama', '$email', '$hp')");
    header("Location: guru.php?msg=saved");
    exit;
}
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM guru WHERE id=$id");
    header("Location: guru.php?msg=deleted");
    exit;
}
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Manajemen Data Guru</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Tambah Guru</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">NIP / NUPTK</label>
                        <input type="text" name="nip" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nama Lengkap & Gelar</label>
                        <input type="text" name="nama_guru" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">No. HP / WA</label>
                        <input type="text" name="no_hp" class="form-control">
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan Data Guru</button>
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
                            <th>No</th>
                            <th>NIP</th>
                            <th>Nama Guru</th>
                            <th>Kontak</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; $q=mysqli_query($conn, "SELECT * FROM guru ORDER BY nama_guru"); while($r=mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($r['nip']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($r['nama_guru']) ?></td>
                            <td class="small"><?= htmlspecialchars($r['email']) ?><br><?= htmlspecialchars($r['no_hp']) ?></td>
                            <td>
                                <a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus guru ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
