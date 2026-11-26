<?php
require 'config/database.php';
cek_login();

if (isset($_POST['simpan'])) {
    $nama   = esc($_POST['nama_sekolah']);
    $npsn   = esc($_POST['npsn']);
    $nss    = esc($_POST['nss']);
    $alamat = esc($_POST['alamat']);
    $kel    = esc($_POST['kelurahan']);
    $kec    = esc($_POST['kecamatan']);
    $kab    = esc($_POST['kab_kota']);
    $prov   = esc($_POST['provinsi']);
    $pos    = esc($_POST['kode_pos']);
    $kepsek = esc($_POST['nama_kepsek']);
    $nip    = esc($_POST['nip_kepsek']);
    $tempat = esc($_POST['tempat_cetak']);
    $tgl    = esc($_POST['tanggal_rapor']);

    $cek = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM identitas_sekolah"))[0];
    if ($cek > 0) {
        mysqli_query($conn, "UPDATE identitas_sekolah SET nama_sekolah='$nama', npsn='$npsn', nss='$nss', alamat='$alamat', kelurahan='$kel', kecamatan='$kec', kab_kota='$kab', provinsi='$prov', kode_pos='$pos', nama_kepsek='$kepsek', nip_kepsek='$nip', tempat_cetak='$tempat', tanggal_rapor='$tgl' WHERE id=1");
    } else {
        mysqli_query($conn, "INSERT INTO identitas_sekolah (nama_sekolah, npsn, nss, alamat, kelurahan, kecamatan, kab_kota, provinsi, kode_pos, nama_kepsek, nip_kepsek, tempat_cetak, tanggal_rapor) VALUES ('$nama','$npsn','$nss','$alamat','$kel','$kec','$kab','$prov','$pos','$kepsek','$nip','$tempat','$tgl')");
    }
    header("Location: identitas_sekolah.php?msg=saved");
    exit;
}

$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM identitas_sekolah LIMIT 1"));
include 'includes/header.php';
?>
<h4 class="fw-bold mb-3">Identitas Satuan Pendidikan (Kop & Pengesahan e-Rapor)</h4>
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2">Data identitas sekolah berhasil diperbarui!</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nama Sekolah</label>
                    <input type="text" name="nama_sekolah" class="form-control" value="<?= htmlspecialchars($d['nama_sekolah'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">NPSN</label>
                    <input type="text" name="npsn" class="form-control" value="<?= htmlspecialchars($d['npsn'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">NSS</label>
                    <input type="text" name="nss" class="form-control" value="<?= htmlspecialchars($d['nss'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Alamat Lengkap Sekolah</label>
                <input type="text" name="alamat" class="form-control" value="<?= htmlspecialchars($d['alamat'] ?? '') ?>" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label small">Kelurahan / Desa</label>
                    <input type="text" name="kelurahan" class="form-control" value="<?= htmlspecialchars($d['kelurahan'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Kecamatan</label>
                    <input type="text" name="kecamatan" class="form-control" value="<?= htmlspecialchars($d['kecamatan'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Kabupaten / Kota</label>
                    <input type="text" name="kab_kota" class="form-control" value="<?= htmlspecialchars($d['kab_kota'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Provinsi</label>
                    <input type="text" name="provinsi" class="form-control" value="<?= htmlspecialchars($d['provinsi'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Kode Pos</label>
                    <input type="text" name="kode_pos" class="form-control" value="<?= htmlspecialchars($d['kode_pos'] ?? '') ?>">
                </div>
            </div>
            <hr>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Nama Kepala Sekolah</label>
                    <input type="text" name="nama_kepsek" class="form-control" value="<?= htmlspecialchars($d['nama_kepsek'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepsek" class="form-control" value="<?= htmlspecialchars($d['nip_kepsek'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Tempat Cetak Rapor</label>
                    <input type="text" name="tempat_cetak" class="form-control" value="<?= htmlspecialchars($d['tempat_cetak'] ?? 'Bandung') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Tanggal Pembagian Rapor</label>
                    <input type="text" name="tanggal_rapor" class="form-control" value="<?= htmlspecialchars($d['tanggal_rapor'] ?? '20 Desember 2026') ?>" required>
                </div>
            </div>
            <button type="submit" name="simpan" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Identitas Sekolah</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
