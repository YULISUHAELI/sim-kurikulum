<?php
require 'config/database.php';
cek_login();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$semester = isset($_GET['semester']) ? esc($_GET['semester']) : '1';
$ta       = isset($_GET['ta']) ? esc($_GET['ta']) : '2026/2027';

$opsi_level = ['Membudaya', 'Berkembang', 'Mulai Berkembang', 'Perlu Bimbingan'];

// Download Template CSV Kokurikuler 7KAIH
if (isset($_GET['download_template']) && $kelas_id) {
    $k_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$kelas_id"));
    $fname  = "Template_Kokurikuler_7KAIH_" . ($k_info['nama_kelas'] ?? 'KELAS') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $fname);
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'NIS', 'Nama_Siswa',
        '1_Bangun_Pagi', '2_Beribadah', '3_Berolahraga',
        '4_Makan_Sehat_Bergizi', '5_Gemar_Belajar', '6_Bermasyarakat', '7_Tidur_Cepat',
        'Predikat_Akhir', 'Catatan_Kokurikuler_7KAIH'
    ]);

    $qs = mysqli_query($conn, "SELECT s.nis, s.nama_siswa, kk.* FROM siswa s LEFT JOIN nilai_kokurikuler_7kaih kk ON kk.siswa_id=s.id AND kk.semester='$semester' AND kk.tahun_ajaran='$ta' WHERE s.kelas_id=$kelas_id ORDER BY s.nama_siswa");
    while ($r = mysqli_fetch_assoc($qs)) {
        fputcsv($out, [
            $r['nis'],
            $r['nama_siswa'],
            $r['bangun_pagi'] ?? 'Membudaya',
            $r['beribadah'] ?? 'Membudaya',
            $r['berolahraga'] ?? 'Berkembang',
            $r['makan_sehat'] ?? 'Membudaya',
            $r['gemar_belajar'] ?? 'Membudaya',
            $r['bermasyarakat'] ?? 'Berkembang',
            $r['tidur_cepat'] ?? 'Berkembang',
            $r['predikat_akhir'] ?? 'Sangat Baik',
            $r['catatan_kokurikuler'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// Import CSV Kokurikuler 7KAIH
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
        while (($data = fgetcsv($handle, 3000, $delim)) !== FALSE) {
            $row_num++;
            if ($row_num === 1) continue;
            if (count($data) < 9) continue;

            $nis  = esc($data[0]);
            $k1   = esc($data[2] ?? 'Berkembang');
            $k2   = esc($data[3] ?? 'Berkembang');
            $k3   = esc($data[4] ?? 'Berkembang');
            $k4   = esc($data[5] ?? 'Berkembang');
            $k5   = esc($data[6] ?? 'Berkembang');
            $k6   = esc($data[7] ?? 'Berkembang');
            $k7   = esc($data[8] ?? 'Berkembang');
            $pred = esc($data[9] ?? 'Baik');
            $cat  = esc($data[10] ?? '');

            $qs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM siswa WHERE nis='$nis' AND kelas_id=$k_id LIMIT 1"));
            if (!$qs) continue;
            $s_id = (int)$qs['id'];

            $sql = "INSERT INTO nilai_kokurikuler_7kaih (siswa_id, kelas_id, tahun_ajaran, semester, bangun_pagi, beribadah, berolahraga, makan_sehat, gemar_belajar, bermasyarakat, tidur_cepat, predikat_akhir, catatan_kokurikuler)
                    VALUES ($s_id, $k_id, '$t_a', '$sem', '$k1', '$k2', '$k3', '$k4', '$k5', '$k6', '$k7', '$pred', '$cat')
                    ON DUPLICATE KEY UPDATE
                    kelas_id=$k_id, bangun_pagi='$k1', beribadah='$k2', berolahraga='$k3', makan_sehat='$k4',
                    gemar_belajar='$k5', bermasyarakat='$k6', tidur_cepat='$k7', predikat_akhir='$pred', catatan_kokurikuler='$cat'";
            mysqli_query($conn, $sql);
        }
        fclose($handle);
    }
    header("Location: input_kokurikuler.php?kelas_id=$k_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=imported");
    exit;
}

// Simpan Manual Kokurikuler 7KAIH
if (isset($_POST['simpan_7kaih'])) {
    $k_id = (int)$_POST['kelas_id'];
    $sem  = esc($_POST['semester']);
    $t_a  = esc($_POST['ta']);

    foreach ($_POST['siswa_id'] as $idx => $s_id) {
        $s_id = (int)$s_id;
        $k1   = esc($_POST['bangun_pagi'][$idx]);
        $k2   = esc($_POST['beribadah'][$idx]);
        $k3   = esc($_POST['berolahraga'][$idx]);
        $k4   = esc($_POST['makan_sehat'][$idx]);
        $k5   = esc($_POST['gemar_belajar'][$idx]);
        $k6   = esc($_POST['bermasyarakat'][$idx]);
        $k7   = esc($_POST['tidur_cepat'][$idx]);
        $pred = esc($_POST['predikat_akhir'][$idx]);
        $cat  = esc($_POST['catatan_kokurikuler'][$idx]);

        // Auto-generate catatan jika dikosongkan
        if (trim($_POST['catatan_kokurikuler'][$idx]) === '') {
            $membudaya = [];
            $map_nama = [
                'bangun pagi' => $k1,
                'beribadah tepat waktu' => $k2,
                'berolahraga teratur' => $k3,
                'makan sehat dan bergizi' => $k4,
                'gemar belajar' => $k5,
                'bermasyarakat' => $k6,
                'tidur cepat' => $k7
            ];
            foreach ($map_nama as $label => $val) {
                if ($val === 'Membudaya') $membudaya[] = $label;
            }
            if (!empty($membudaya)) {
                $cat = esc("Ananda menunjukkan pembiasaan karakter yang sangat baik dan telah membudaya dalam kebiasaan " . implode(", ", $membudaya) . ".");
            } else {
                $cat = esc("Ananda terus berkembang dalam menerapkan 7 Kebiasaan Anak Indonesia Hebat di lingkungan sekolah maupun rumah.");
            }
        }

        $sql = "INSERT INTO nilai_kokurikuler_7kaih (siswa_id, kelas_id, tahun_ajaran, semester, bangun_pagi, beribadah, berolahraga, makan_sehat, gemar_belajar, bermasyarakat, tidur_cepat, predikat_akhir, catatan_kokurikuler)
                VALUES ($s_id, $k_id, '$t_a', '$sem', '$k1', '$k2', '$k3', '$k4', '$k5', '$k6', '$k7', '$pred', '$cat')
                ON DUPLICATE KEY UPDATE
                kelas_id=$k_id, bangun_pagi='$k1', beribadah='$k2', berolahraga='$k3', makan_sehat='$k4',
                gemar_belajar='$k5', bermasyarakat='$k6', tidur_cepat='$k7', predikat_akhir='$pred', catatan_kokurikuler='$cat'";
        mysqli_query($conn, $sql);
    }
    header("Location: input_kokurikuler.php?kelas_id=$k_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=saved");
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="fw-bold mb-0">Input Nilai Kokurikuler — 7 Kebiasaan Anak Indonesia Hebat (7KAIH)</h4>
        <small class="text-muted">Penilaian pembiasaan karakter 7KAIH (Bangun Pagi, Beribadah, Berolahraga, Makan Sehat, Gemar Belajar, Bermasyarakat, Tidur Cepat).</small>
    </div>
    <?php if ($kelas_id): ?>
    <div class="d-flex gap-2">
        <a href="?kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>&download_template=1" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template CSV
        </a>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport7KAIH">
            <i class="bi bi-upload me-1"></i>Import 7KAIH (CSV)
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2">
        <?= $_GET['msg'] === 'imported' ? 'Data penilaian Kokurikuler 7KAIH berhasil diimpor dari CSV!' : 'Data penilaian Kokurikuler 7KAIH berhasil disimpan!' ?>
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
        SELECT s.id as siswa_id, s.nis, s.nama_siswa, kk.*
        FROM siswa s
        LEFT JOIN nilai_kokurikuler_7kaih kk ON kk.siswa_id=s.id AND kk.semester='$semester' AND kk.tahun_ajaran='$ta'
        WHERE s.kelas_id=$kelas_id
        ORDER BY s.nama_siswa
    ");
?>
<form method="POST">
    <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
    <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
    <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Lembar Penilaian 7 Kebiasaan Anak Indonesia Hebat (7KAIH)</span>
            <a href="rapor_kokurikuler.php?kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Lihat & Cetak Rapor Kokurikuler 7KAIH
            </a>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead class="text-center">
                    <tr>
                        <th width="3%">No</th>
                        <th width="15%" class="text-start">Nama Siswa</th>
                        <th width="9%">1. Bangun Pagi</th>
                        <th width="9%">2. Beribadah</th>
                        <th width="9%">3. Berolahraga</th>
                        <th width="9%">4. Makan Sehat</th>
                        <th width="9%">5. Gemar Belajar</th>
                        <th width="9%">6. Bermasyarakat</th>
                        <th width="9%">7. Tidur Cepat</th>
                        <th width="9%">Predikat</th>
                        <th width="19%">Deskripsi / Catatan Kokurikuler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; while ($r = mysqli_fetch_assoc($q_siswa)): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td>
                            <input type="hidden" name="siswa_id[]" value="<?= $r['siswa_id'] ?>">
                            <strong class="small"><?= htmlspecialchars($r['nama_siswa']) ?></strong><br>
                            <span class="text-muted" style="font-size: 0.75rem;">NIS: <?= htmlspecialchars($r['nis']) ?></span>
                        </td>
                        <?php
                        $fields = ['bangun_pagi', 'beribadah', 'berolahraga', 'makan_sehat', 'gemar_belajar', 'bermasyarakat', 'tidur_cepat'];
                        foreach ($fields as $fld):
                            $cur = $r[$fld] ?? 'Membudaya';
                        ?>
                        <td>
                            <select name="<?= $fld ?>[]" class="form-select form-select-sm" style="font-size: 0.78rem;">
                                <?php foreach ($opsi_level as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $cur===$opt?'selected':'' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <?php endforeach; ?>
                        <td>
                            <select name="predikat_akhir[]" class="form-select form-select-sm fw-bold" style="font-size: 0.78rem;">
                                <?php $pa = $r['predikat_akhir'] ?? 'Sangat Baik'; ?>
                                <option value="Sangat Baik" <?= $pa=='Sangat Baik'?'selected':'' ?>>Sangat Baik</option>
                                <option value="Baik" <?= $pa=='Baik'?'selected':'' ?>>Baik</option>
                                <option value="Cukup" <?= $pa=='Cukup'?'selected':'' ?>>Cukup</option>
                            </select>
                        </td>
                        <td>
                            <textarea name="catatan_kokurikuler[]" class="form-control form-control-sm" rows="2" placeholder="Kosongkan untuk generate otomatis..."><?= htmlspecialchars($r['catatan_kokurikuler'] ?? '') ?></textarea>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" name="simpan_7kaih" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i>Simpan Nilai Kokurikuler 7KAIH</button>
            </div>
        </div>
    </div>
</form>

<!-- Modal Import CSV 7KAIH -->
<div class="modal fade" id="modalImport7KAIH" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Nilai Kokurikuler 7KAIH (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">
                <p class="small text-muted">Unduh <strong>Template CSV</strong> 7KAIH terlebih dahulu. Isi setiap kebiasaan dengan: <code>Membudaya</code>, <code>Berkembang</code>, <code>Mulai Berkembang</code>, atau <code>Perlu Bimbingan</code>.</p>
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
