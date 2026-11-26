<?php
require 'config/database.php';
cek_login();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$semester = isset($_GET['semester']) ? esc($_GET['semester']) : '1';
$ta       = isset($_GET['ta']) ? esc($_GET['ta']) : '2026/2027';

// Download Template CSV Rekap Kehadiran
if (isset($_GET['download_template']) && $kelas_id) {
    $k_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$kelas_id"));
    $fname  = "Template_Kehadiran_" . ($k_info['nama_kelas'] ?? 'KELAS') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $fname);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIS', 'Nama_Siswa', 'Sakit_Hari', 'Izin_Hari', 'Tanpa_Keterangan_Alpha_Hari', 'Catatan_Wali_Kelas', 'Keputusan_Kenaikan_Kelulusan']);

    $qs = mysqli_query($conn, "SELECT s.nis, s.nama_siswa, dw.* FROM siswa s LEFT JOIN data_rapor_wali dw ON dw.siswa_id=s.id AND dw.semester='$semester' AND dw.tahun_ajaran='$ta' WHERE s.kelas_id=$kelas_id ORDER BY s.nama_siswa");
    while ($r = mysqli_fetch_assoc($qs)) {
        fputcsv($out, [
            $r['nis'],
            $r['nama_siswa'],
            $r['sakit'] ?? 0,
            $r['izin'] ?? 0,
            $r['alpha'] ?? 0,
            $r['catatan_wali'] ?? 'Terus tingkatkan semangat belajar dan kedisiplinan di sekolah.',
            $r['status_kenaikan'] ?? ($semester == '1' ? 'Lanjut ke Semester Genap' : 'Naik ke Kelas Berikutnya')
        ]);
    }
    fclose($out);
    exit;
}

// Import CSV Rekap Kehadiran
if (isset($_POST['import_csv'])) {
    $k_id = (int)$_POST['kelas_id'];
    $sem  = esc($_POST['semester']);
    $t_a  = esc($_POST['ta']);

    if (!empty($_FILES['file_csv']['tmp_name'])) {
        $handle = fopen($_FILES['file_csv']['tmp_name'], "r");
        $first_line = fgets($handle);
        rewind($handle);
        $delim = (strpos($first_line, ';') !== false) ? ';' : ',';

        $row_num = 0;
        while (($data = fgetcsv($handle, 2000, $delim)) !== FALSE) {
            $row_num++;
            if ($row_num === 1) continue;
            if (count($data) < 5) continue;

            $nis   = esc($data[0]);
            $sakit = (int)($data[2] ?? 0);
            $izin  = (int)($data[3] ?? 0);
            $alpha = (int)($data[4] ?? 0);
            $cat   = esc($data[5] ?? '');
            $stat  = esc($data[6] ?? ($sem == '1' ? 'Lanjut ke Semester Genap' : 'Naik ke Kelas Berikutnya'));

            $qs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM siswa WHERE nis='$nis' AND kelas_id=$k_id LIMIT 1"));
            if (!$qs) continue;
            $s_id = (int)$qs['id'];

            $sql = "INSERT INTO data_rapor_wali (siswa_id, kelas_id, tahun_ajaran, semester, sakit, izin, alpha, catatan_wali, status_kenaikan)
                    VALUES ($s_id, $k_id, '$t_a', '$sem', $sakit, $izin, $alpha, '$cat', '$stat')
                    ON DUPLICATE KEY UPDATE
                    kelas_id=$k_id, sakit=$sakit, izin=$izin, alpha=$alpha, catatan_wali='$cat', status_kenaikan='$stat'";
            mysqli_query($conn, $sql);
        }
        fclose($handle);
    }
    header("Location: rekap_kehadiran.php?kelas_id=$k_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=imported");
    exit;
}

// Simpan Manual Rekap Kehadiran
if (isset($_POST['simpan_kehadiran'])) {
    $k_id = (int)$_POST['kelas_id'];
    $sem  = esc($_POST['semester']);
    $t_a  = esc($_POST['ta']);

    foreach ($_POST['siswa_id'] as $idx => $s_id) {
        $s_id  = (int)$s_id;
        $sakit = (int)$_POST['sakit'][$idx];
        $izin  = (int)$_POST['izin'][$idx];
        $alpha = (int)$_POST['alpha'][$idx];
        $cat   = esc($_POST['catatan_wali'][$idx]);
        $stat  = esc($_POST['status_kenaikan'][$idx]);

        $sql = "INSERT INTO data_rapor_wali (siswa_id, kelas_id, tahun_ajaran, semester, sakit, izin, alpha, catatan_wali, status_kenaikan)
                VALUES ($s_id, $k_id, '$t_a', '$sem', $sakit, $izin, $alpha, '$cat', '$stat')
                ON DUPLICATE KEY UPDATE
                kelas_id=$k_id, sakit=$sakit, izin=$izin, alpha=$alpha, catatan_wali='$cat', status_kenaikan='$stat'";
        mysqli_query($conn, $sql);
    }
    header("Location: rekap_kehadiran.php?kelas_id=$k_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=saved");
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="fw-bold mb-0">Rekap Kehadiran & Catatan Wali Kelas</h4>
        <small class="text-muted">Pengelolaan rekap ketidakhadiran (Sakit, Izin, Tanpa Keterangan/Alpha) serta catatan wali kelas untuk e-Rapor.</small>
    </div>
    <?php if ($kelas_id): ?>
    <div class="d-flex gap-2">
        <a href="?kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>&download_template=1" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template CSV
        </a>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportKehadiran">
            <i class="bi bi-upload me-1"></i>Import Kehadiran (CSV)
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2">
        <?= $_GET['msg'] === 'imported' ? 'Data rekap kehadiran berhasil diimpor dari file CSV!' : 'Data rekap kehadiran dan catatan wali kelas berhasil disimpan!' ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Pilih Kelas</label>
                <select name="kelas_id" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php $k=mysqli_query($conn, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas"); while($rk=mysqli_fetch_assoc($k)): ?>
                        <option value="<?= $rk['id'] ?>" <?= $kelas_id==$rk['id']?'selected':'' ?>><?= htmlspecialchars($rk['nama_kelas']) ?> (Tingkat <?= $rk['tingkat'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Semester</label>
                <select name="semester" class="form-select">
                    <option value="1" <?= $semester=='1'?'selected':'' ?>>Ganjil (1)</option>
                    <option value="2" <?= $semester=='2'?'selected':'' ?>>Genap (2)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Tahun Ajaran</label>
                <input type="text" name="ta" class="form-control" value="<?= htmlspecialchars($ta) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($kelas_id):
    $q_siswa = mysqli_query($conn, "
        SELECT s.id as siswa_id, s.nis, s.nama_siswa, dw.*
        FROM siswa s
        LEFT JOIN data_rapor_wali dw ON dw.siswa_id=s.id AND dw.semester='$semester' AND dw.tahun_ajaran='$ta'
        WHERE s.kelas_id=$kelas_id
        ORDER BY s.nama_siswa
    ");
?>
<form method="POST">
    <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
    <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
    <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Lembar Rekap Ketidakhadiran & Catatan Wali Kelas</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="22%">Nama Siswa / NIS</th>
                        <th width="9%" class="text-center">Sakit (Hari)</th>
                        <th width="9%" class="text-center">Izin (Hari)</th>
                        <th width="10%" class="text-center">Alpha (Hari)</th>
                        <th width="28%">Catatan Wali Kelas</th>
                        <th width="18%">Keputusan / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; while ($r = mysqli_fetch_assoc($q_siswa)): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td>
                            <input type="hidden" name="siswa_id[]" value="<?= $r['siswa_id'] ?>">
                            <strong><?= htmlspecialchars($r['nama_siswa']) ?></strong><br>
                            <small class="text-muted">NIS: <?= htmlspecialchars($r['nis']) ?></small>
                        </td>
                        <td><input type="number" min="0" name="sakit[]" class="form-control form-control-sm text-center" value="<?= $r['sakit'] ?? 0 ?>"></td>
                        <td><input type="number" min="0" name="izin[]" class="form-control form-control-sm text-center" value="<?= $r['izin'] ?? 0 ?>"></td>
                        <td><input type="number" min="0" name="alpha[]" class="form-control form-control-sm text-center" value="<?= $r['alpha'] ?? 0 ?>"></td>
                        <td>
                            <textarea name="catatan_wali[]" class="form-control form-control-sm" rows="2" placeholder="Catatan motivasi dari wali kelas..."><?= htmlspecialchars($r['catatan_wali'] ?? '') ?></textarea>
                        </td>
                        <td>
                            <input type="text" name="status_kenaikan[]" class="form-control form-control-sm" value="<?= htmlspecialchars($r['status_kenaikan'] ?? ($semester=='1' ? 'Lanjut ke Semester Genap' : 'Naik ke Kelas Berikutnya')) ?>">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" name="simpan_kehadiran" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i>Simpan Rekap Kehadiran</button>
            </div>
        </div>
    </div>
</form>

<!-- Modal Import CSV Kehadiran -->
<div class="modal fade" id="modalImportKehadiran" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Rekap Kehadiran (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">
                <p class="small text-muted">Unduh <strong>Template CSV</strong> Rekap Kehadiran terlebih dahulu, lengkapi jumlah hari Sakit, Izin, dan Alpha di Excel, simpan sebagai <code>.csv</code> lalu unggah di sini.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih File CSV</label>
                    <input type="file" name="file_csv" accept=".csv" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="import_csv" class="btn btn-success"><i class="bi bi-upload me-1"></i>Proses Import</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
