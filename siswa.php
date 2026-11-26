<?php
require 'config/database.php';
cek_login();

// Download Template CSV Data Siswa
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Template_Data_Siswa_SMP_Fase_D.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIS', 'NISN', 'Nama_Siswa', 'Jenis_Kelamin_L_P', 'Nama_Kelas', 'Alamat']);
    fputcsv($out, ['26003', '0134567894', 'Bagas Pratama', 'L', 'VII-A', 'Jl. Melati No. 14']);
    fputcsv($out, ['26004', '0134567895', 'Citra Kirana', 'P', 'VII-A', 'Jl. Anggrek No. 8']);
    fclose($out);
    exit;
}

// Import CSV Data Siswa
if (isset($_POST['import_csv'])) {
    $imported_count = 0;
    if (!empty($_FILES['file_csv']['tmp_name'])) {
        $handle = fopen($_FILES['file_csv']['tmp_name'], "r");
        $first_line = fgets($handle);
        rewind($handle);
        $delim = (strpos($first_line, ';') !== false) ? ';' : ',';

        $row_num = 0;
        while (($data = fgetcsv($handle, 2000, $delim)) !== FALSE) {
            $row_num++;
            if ($row_num === 1) continue; // skip header
            if (count($data) < 4) continue;

            $nis   = esc($data[0] ?? '');
            $nisn  = esc($data[1] ?? '');
            $nama  = esc($data[2] ?? '');
            $jk_raw= strtoupper(trim($data[3] ?? 'L'));
            $jk    = ($jk_raw === 'P' || $jk_raw === 'PEREMPUAN') ? 'P' : 'L';
            $kls_nm= esc($data[4] ?? '');
            $alamat= esc($data[5] ?? '');

            if ($nis === '' || $nama === '') continue;

            // Cari kelas_id berdasarkan nama_kelas
            $kelas_sql = "NULL";
            if ($kls_nm !== '') {
                $qk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM kelas WHERE nama_kelas='$kls_nm' LIMIT 1"));
                if ($qk) {
                    $kelas_sql = (int)$qk['id'];
                }
            }

            // Cek apakah NIS sudah ada -> update jika ada, insert jika baru
            $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM siswa WHERE nis='$nis' LIMIT 1"));
            if ($cek) {
                $sid = (int)$cek['id'];
                mysqli_query($conn, "UPDATE siswa SET nisn='$nisn', nama_siswa='$nama', jenis_kelamin='$jk', kelas_id=$kelas_sql, alamat='$alamat' WHERE id=$sid");
            } else {
                mysqli_query($conn, "INSERT INTO siswa (nis, nisn, nama_siswa, jenis_kelamin, kelas_id, alamat) VALUES ('$nis', '$nisn', '$nama', '$jk', $kelas_sql, '$alamat')");
            }
            $imported_count++;
        }
        fclose($handle);
    }
    header("Location: siswa.php?msg=imported&count=$imported_count");
    exit;
}

if (isset($_POST['simpan'])) {
    $nis   = esc($_POST['nis']);
    $nisn  = esc($_POST['nisn']);
    $nama  = esc($_POST['nama_siswa']);
    $jk    = esc($_POST['jenis_kelamin']);
    $kelas = (int)$_POST['kelas_id'];
    $alamat= esc($_POST['alamat']);
    mysqli_query($conn, "INSERT INTO siswa (nis, nisn, nama_siswa, jenis_kelamin, kelas_id, alamat) VALUES ('$nis', '$nisn', '$nama', '$jk', $kelas, '$alamat')");
    header("Location: siswa.php?msg=saved");
    exit;
}
if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM siswa WHERE id=".(int)$_GET['hapus']);
    header("Location: siswa.php?msg=deleted");
    exit;
}

$filter_kelas = isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '' ? (int)$_GET['kelas_id'] : 0;
include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">Input & Manajemen Data Siswa</h4>
        <small class="text-muted">Kelola data peserta didik secara manual atau unggah massal melalui Template CSV/Excel.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="?download_template=1" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template CSV
        </a>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportSiswa">
            <i class="bi bi-upload me-1"></i>Import Data Siswa (CSV)
        </button>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2">
        <?php
        if ($_GET['msg'] === 'imported') {
            echo "Berhasil mengimpor/memperbarui <strong>" . (int)($_GET['count'] ?? 0) . "</strong> data siswa dari file CSV!";
        } elseif ($_GET['msg'] === 'deleted') {
            echo "Data siswa berhasil dihapus.";
        } else {
            echo "Data siswa baru berhasil disimpan!";
        }
        ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Form Input Data Siswa</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">NIS</label>
                            <input type="text" name="nis" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">NISN</label>
                            <input type="text" name="nisn" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nama Lengkap Siswa</label>
                        <input type="text" name="nama_siswa" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Penempatan Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <?php $k=mysqli_query($conn, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas"); while($rk=mysqli_fetch_assoc($k)): ?>
                                    <option value="<?= $rk['id'] ?>"><?= htmlspecialchars($rk['nama_kelas']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan Data Siswa</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Daftar Siswa Fase D</span>
                <form method="GET" class="d-flex gap-2">
                    <select name="kelas_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        <?php $k2=mysqli_query($conn, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas"); while($rk2=mysqli_fetch_assoc($k2)): ?>
                            <option value="<?= $rk2['id'] ?>" <?= $filter_kelas==$rk2['id']?'selected':'' ?>><?= htmlspecialchars($rk2['nama_kelas']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS / NISN</th>
                            <th>Nama Siswa</th>
                            <th>L/P</th>
                            <th>Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = $filter_kelas ? "WHERE s.kelas_id = $filter_kelas" : "";
                        $q = mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id=k.id $where ORDER BY k.nama_kelas, s.nama_siswa");
                        $no = 1;
                        while($r = mysqli_fetch_assoc($q)):
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="small"><?= htmlspecialchars($r['nis']) ?> / <?= htmlspecialchars($r['nisn']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($r['nama_siswa']) ?></td>
                            <td><?= $r['jenis_kelamin'] ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($r['nama_kelas'] ?? 'Belum diatur') ?></span></td>
                            <td><a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus data siswa?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import CSV Data Siswa -->
<div class="modal fade" id="modalImportSiswa" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Data Siswa (CSV / Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    Unduh <strong>Template CSV</strong> terlebih dahulu, lengkapi kolom <code>NIS</code>, <code>NISN</code>, <code>Nama_Siswa</code>, <code>Jenis_Kelamin_L_P</code> (L/P), <code>Nama_Kelas</code> (misal: <code>VII-A</code>), dan <code>Alamat</code> di Excel/WPS lalu simpan sebagai <code>.csv</code>.
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih File CSV</label>
                    <input type="file" name="file_csv" accept=".csv" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="import_csv" class="btn btn-success"><i class="bi bi-upload me-1"></i>Proses Import Siswa</button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
