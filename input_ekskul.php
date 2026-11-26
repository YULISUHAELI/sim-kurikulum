<?php
require 'config/database.php';
cek_login();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$semester = isset($_GET['semester']) ? esc($_GET['semester']) : '1';
$ta       = isset($_GET['ta']) ? esc($_GET['ta']) : '2026/2027';

// Download Template CSV Ekstrakurikuler
if (isset($_GET['download_template']) && $kelas_id) {
    $k_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$kelas_id"));
    $fname  = "Template_Ekstrakurikuler_" . ($k_info['nama_kelas'] ?? 'KELAS') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $fname);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIS', 'Nama_Siswa', 'Ekskul_1_Nama', 'Ekskul_1_Predikat', 'Ekskul_1_Keterangan', 'Ekskul_2_Nama', 'Ekskul_2_Predikat', 'Ekskul_2_Keterangan']);

    $qs = mysqli_query($conn, "SELECT s.nis, s.nama_siswa, dw.* FROM siswa s LEFT JOIN data_rapor_wali dw ON dw.siswa_id=s.id AND dw.semester='$semester' AND dw.tahun_ajaran='$ta' WHERE s.kelas_id=$kelas_id ORDER BY s.nama_siswa");
    while ($r = mysqli_fetch_assoc($qs)) {
        fputcsv($out, [
            $r['nis'],
            $r['nama_siswa'],
            $r['ekskul_1_nama'] ?? 'Pramuka',
            $r['ekskul_1_predikat'] ?? 'Sangat Baik',
            $r['ekskul_1_ket'] ?? 'Aktif mengikuti kegiatan latihan rutin.',
            $r['ekskul_2_nama'] ?? '',
            $r['ekskul_2_predikat'] ?? '',
            $r['ekskul_2_ket'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// Import CSV Ekstrakurikuler
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
            if (count($data) < 4) continue;

            $nis   = esc($data[0]);
            $ek1_n = esc($data[2] ?? '');
            $ek1_p = esc($data[3] ?? '');
            $ek1_k = esc($data[4] ?? '');
            $ek2_n = esc($data[5] ?? '');
            $ek2_p = esc($data[6] ?? '');
            $ek2_k = esc($data[7] ?? '');

            $qs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM siswa WHERE nis='$nis' AND kelas_id=$k_id LIMIT 1"));
            if (!$qs) continue;
            $s_id = (int)$qs['id'];

            $sql = "INSERT INTO data_rapor_wali (siswa_id, kelas_id, tahun_ajaran, semester, ekskul_1_nama, ekskul_1_predikat, ekskul_1_ket, ekskul_2_nama, ekskul_2_predikat, ekskul_2_ket)
                    VALUES ($s_id, $k_id, '$t_a', '$sem', '$ek1_n', '$ek1_p', '$ek1_k', '$ek2_n', '$ek2_p', '$ek2_k')
                    ON DUPLICATE KEY UPDATE
                    kelas_id=$k_id, ekskul_1_nama='$ek1_n', ekskul_1_predikat='$ek1_p', ekskul_1_ket='$ek1_k',
                    ekskul_2_nama='$ek2_n', ekskul_2_predikat='$ek2_p', ekskul_2_ket='$ek2_k'";
            mysqli_query($conn, $sql);
        }
        fclose($handle);
    }
    header("Location: input_ekskul.php?kelas_id=$k_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=imported");
    exit;
}

// Simpan Manual Ekstrakurikuler
if (isset($_POST['simpan_ekskul'])) {
    $k_id = (int)$_POST['kelas_id'];
    $sem  = esc($_POST['semester']);
    $t_a  = esc($_POST['ta']);

    foreach ($_POST['siswa_id'] as $idx => $s_id) {
        $s_id  = (int)$s_id;
        $ek1_n = esc($_POST['ekskul_1_nama'][$idx]);
        $ek1_p = esc($_POST['ekskul_1_predikat'][$idx]);
        $ek1_k = esc($_POST['ekskul_1_ket'][$idx]);
        $ek2_n = esc($_POST['ekskul_2_nama'][$idx]);
        $ek2_p = esc($_POST['ekskul_2_predikat'][$idx]);
        $ek2_k = esc($_POST['ekskul_2_ket'][$idx]);

        $sql = "INSERT INTO data_rapor_wali (siswa_id, kelas_id, tahun_ajaran, semester, ekskul_1_nama, ekskul_1_predikat, ekskul_1_ket, ekskul_2_nama, ekskul_2_predikat, ekskul_2_ket)
                VALUES ($s_id, $k_id, '$t_a', '$sem', '$ek1_n', '$ek1_p', '$ek1_k', '$ek2_n', '$ek2_p', '$ek2_k')
                ON DUPLICATE KEY UPDATE
                kelas_id=$k_id, ekskul_1_nama='$ek1_n', ekskul_1_predikat='$ek1_p', ekskul_1_ket='$ek1_k',
                ekskul_2_nama='$ek2_n', ekskul_2_predikat='$ek2_p', ekskul_2_ket='$ek2_k'";
        mysqli_query($conn, $sql);
    }
    header("Location: input_ekskul.php?kelas_id=$k_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=saved");
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="fw-bold mb-0">Input Nilai Ekstrakurikuler</h4>
        <small class="text-muted">Pengelolaan nilai kegiatan ekstrakurikuler wajib (Pramuka) dan pilihan untuk e-Rapor Kurikulum Merdeka.</small>
    </div>
    <?php if ($kelas_id): ?>
    <div class="d-flex gap-2">
        <a href="?kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>&download_template=1" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template CSV
        </a>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportEkskul">
            <i class="bi bi-upload me-1"></i>Import Ekskul (CSV)
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2">
        <?= $_GET['msg'] === 'imported' ? 'Data nilai ekstrakurikuler berhasil diimpor dari CSV!' : 'Data nilai ekstrakurikuler berhasil disimpan!' ?>
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
        <div class="card-header bg-white fw-bold">Daftar Nilai Ekstrakurikuler Peserta Didik</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="20%">Nama Siswa / NIS</th>
                        <th width="38%">Ekstrakurikuler 1 (Wajib / Utama)</th>
                        <th width="38%">Ekstrakurikuler 2 (Pilihan / Opsional)</th>
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
                        <td>
                            <div class="row g-1 mb-1">
                                <div class="col-7">
                                    <input type="text" name="ekskul_1_nama[]" class="form-control form-control-sm" placeholder="Nama Ekskul (mis: Pramuka)" value="<?= htmlspecialchars($r['ekskul_1_nama'] ?? 'Pramuka') ?>">
                                </div>
                                <div class="col-5">
                                    <select name="ekskul_1_predikat[]" class="form-select form-select-sm">
                                        <option value="Sangat Baik" <?= ($r['ekskul_1_predikat'] ?? '')=='Sangat Baik'?'selected':'' ?>>Sangat Baik</option>
                                        <option value="Baik" <?= ($r['ekskul_1_predikat'] ?? '')=='Baik'?'selected':'' ?>>Baik</option>
                                        <option value="Cukup" <?= ($r['ekskul_1_predikat'] ?? '')=='Cukup'?'selected':'' ?>>Cukup</option>
                                    </select>
                                </div>
                            </div>
                            <input type="text" name="ekskul_1_ket[]" class="form-control form-control-sm" placeholder="Deskripsi keaktifan & capaian ekskul 1..." value="<?= htmlspecialchars($r['ekskul_1_ket'] ?? '') ?>">
                        </td>
                        <td>
                            <div class="row g-1 mb-1">
                                <div class="col-7">
                                    <input type="text" name="ekskul_2_nama[]" class="form-control form-control-sm" placeholder="Ekskul 2 (mis: Futsal / PMR)" value="<?= htmlspecialchars($r['ekskul_2_nama'] ?? '') ?>">
                                </div>
                                <div class="col-5">
                                    <select name="ekskul_2_predikat[]" class="form-select form-select-sm">
                                        <option value="">-- Predikat --</option>
                                        <option value="Sangat Baik" <?= ($r['ekskul_2_predikat'] ?? '')=='Sangat Baik'?'selected':'' ?>>Sangat Baik</option>
                                        <option value="Baik" <?= ($r['ekskul_2_predikat'] ?? '')=='Baik'?'selected':'' ?>>Baik</option>
                                        <option value="Cukup" <?= ($r['ekskul_2_predikat'] ?? '')=='Cukup'?'selected':'' ?>>Cukup</option>
                                    </select>
                                </div>
                            </div>
                            <input type="text" name="ekskul_2_ket[]" class="form-control form-control-sm" placeholder="Deskripsi keaktifan & capaian ekskul 2..." value="<?= htmlspecialchars($r['ekskul_2_ket'] ?? '') ?>">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" name="simpan_ekskul" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i>Simpan Nilai Ekstrakurikuler</button>
            </div>
        </div>
    </div>
</form>

<!-- Modal Import CSV Ekstrakurikuler -->
<div class="modal fade" id="modalImportEkskul" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Nilai Ekstrakurikuler (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">
                <p class="small text-muted">Gunakan file <strong>Template CSV</strong> agar NIS siswa dan format kolom ekstrakurikuler sesuai.</p>
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
