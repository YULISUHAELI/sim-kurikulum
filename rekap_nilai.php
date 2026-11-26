<?php
require 'config/database.php';
cek_login();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
$semester = isset($_GET['semester']) ? esc($_GET['semester']) : '1';
$ta       = isset($_GET['ta']) ? esc($_GET['ta']) : '2026/2027';

// Download Template CSV Rekap Nilai
if (isset($_GET['download_template']) && $kelas_id && $mapel_id) {
    $k_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$kelas_id"));
    $m_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kode_mapel FROM mapel WHERE id=$mapel_id"));
    $fname  = "Template_Nilai_" . ($m_info['kode_mapel'] ?? 'MAPEL') . "_" . ($k_info['nama_kelas'] ?? 'KELAS') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $fname);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIS', 'Nama_Siswa', 'Nilai_Formatif', 'Nilai_Sumatif_Lingkup', 'Nilai_Sumatif_Akhir', 'Kode_TP_Tercapai', 'Kode_TP_Perlu_Bimbingan', 'Deskripsi_Capaian_Opsional']);

    $qs = mysqli_query($conn, "SELECT s.nis, s.nama_siswa, rn.* FROM siswa s LEFT JOIN rekap_nilai rn ON rn.siswa_id=s.id AND rn.mapel_id=$mapel_id AND rn.semester='$semester' AND rn.tahun_ajaran='$ta' WHERE s.kelas_id=$kelas_id ORDER BY s.nama_siswa");
    while ($r = mysqli_fetch_assoc($qs)) {
        fputcsv($out, [
            $r['nis'],
            $r['nama_siswa'],
            $r['nilai_formatif'] ?? 0,
            $r['nilai_sumatif_lingkup'] ?? 0,
            $r['nilai_sumatif_akhir'] ?? 0,
            '',
            '',
            $r['deskripsi_capaian'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// Import CSV Rekap Nilai
if (isset($_POST['import_csv'])) {
    $k_id = (int)$_POST['kelas_id'];
    $m_id = (int)$_POST['mapel_id'];
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
            if ($row_num === 1) continue; // skip header
            if (count($data) < 5) continue;

            $nis  = esc($data[0]);
            $nf   = (float)str_replace(',', '.', $data[2]);
            $nsl  = (float)str_replace(',', '.', $data[3]);
            $nsa  = (float)str_replace(',', '.', $data[4]);
            $na   = round(($nf * 0.30) + ($nsl * 0.40) + ($nsa * 0.30));

            $kode_ok = esc($data[5] ?? '');
            $kode_no = esc($data[6] ?? '');
            $desk_in = trim($data[7] ?? '');

            $qs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM siswa WHERE nis='$nis' AND kelas_id=$k_id LIMIT 1"));
            if (!$qs) continue;
            $s_id = (int)$qs['id'];

            $tp_ok = "NULL";
            $tp_no = "NULL";
            $parts = [];

            if ($kode_ok !== '') {
                $q_ok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, deskripsi_tp FROM tujuan_pembelajaran WHERE kode_tp='$kode_ok' LIMIT 1"));
                if ($q_ok) {
                    $tp_ok = (int)$q_ok['id'];
                    $parts[] = "Menunjukkan penguasaan yang baik dalam " . lcfirst(rtrim($q_ok['deskripsi_tp'], '.')) . ".";
                }
            }
            if ($kode_no !== '') {
                $q_no = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, deskripsi_tp FROM tujuan_pembelajaran WHERE kode_tp='$kode_no' LIMIT 1"));
                if ($q_no) {
                    $tp_no = (int)$q_no['id'];
                    $parts[] = "Perlu bimbingan dan pendampingan lebih lanjut dalam " . lcfirst(rtrim($q_no['deskripsi_tp'], '.')) . ".";
                }
            }

            $desk = $desk_in !== '' ? esc($desk_in) : esc(implode(" ", $parts));

            $sql = "INSERT INTO rekap_nilai (siswa_id, mapel_id, kelas_id, tahun_ajaran, semester, nilai_formatif, nilai_sumatif_lingkup, nilai_sumatif_akhir, nilai_akhir, tp_tercapai_id, tp_perlu_bimbingan_id, deskripsi_capaian)
                    VALUES ($s_id, $m_id, $k_id, '$t_a', '$sem', $nf, $nsl, $nsa, $na, $tp_ok, $tp_no, '$desk')
                    ON DUPLICATE KEY UPDATE
                    kelas_id=$k_id, nilai_formatif=$nf, nilai_sumatif_lingkup=$nsl, nilai_sumatif_akhir=$nsa, nilai_akhir=$na,
                    tp_tercapai_id=$tp_ok, tp_perlu_bimbingan_id=$tp_no, deskripsi_capaian='$desk'";
            mysqli_query($conn, $sql);
        }
        fclose($handle);
    }
    header("Location: rekap_nilai.php?kelas_id=$k_id&mapel_id=$m_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=imported");
    exit;
}

// Simpan batch nilai & deskripsi capaian kompetensi manual
if (isset($_POST['simpan_nilai'])) {
    $k_id = (int)$_POST['kelas_id'];
    $m_id = (int)$_POST['mapel_id'];
    $sem  = esc($_POST['semester']);
    $t_a  = esc($_POST['ta']);

    foreach ($_POST['siswa_id'] as $idx => $s_id) {
        $s_id   = (int)$s_id;
        $nf     = (float)$_POST['nilai_formatif'][$idx];
        $nsl    = (float)$_POST['nilai_sumatif_lingkup'][$idx];
        $nsa    = (float)$_POST['nilai_sumatif_akhir'][$idx];
        $na     = round(($nf * 0.30) + ($nsl * 0.40) + ($nsa * 0.30));

        $tp_ok  = !empty($_POST['tp_tercapai'][$idx]) ? (int)$_POST['tp_tercapai'][$idx] : "NULL";
        $tp_no  = !empty($_POST['tp_bimbingan'][$idx]) ? (int)$_POST['tp_bimbingan'][$idx] : "NULL";
        $desk   = esc($_POST['deskripsi_capaian'][$idx]);

        if (trim($_POST['deskripsi_capaian'][$idx]) === '') {
            $parts = [];
            if ($tp_ok !== "NULL") {
                $q_ok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT deskripsi_tp FROM tujuan_pembelajaran WHERE id=$tp_ok"));
                if ($q_ok) $parts[] = "Menunjukkan penguasaan yang baik dalam " . lcfirst(rtrim($q_ok['deskripsi_tp'], '.')) . ".";
            }
            if ($tp_no !== "NULL") {
                $q_no = mysqli_fetch_assoc(mysqli_query($conn, "SELECT deskripsi_tp FROM tujuan_pembelajaran WHERE id=$tp_no"));
                if ($q_no) $parts[] = "Perlu bimbingan dan pendampingan lebih lanjut dalam " . lcfirst(rtrim($q_no['deskripsi_tp'], '.')) . ".";
            }
            $desk = esc(implode(" ", $parts));
        }

        $sql = "INSERT INTO rekap_nilai (siswa_id, mapel_id, kelas_id, tahun_ajaran, semester, nilai_formatif, nilai_sumatif_lingkup, nilai_sumatif_akhir, nilai_akhir, tp_tercapai_id, tp_perlu_bimbingan_id, deskripsi_capaian)
                VALUES ($s_id, $m_id, $k_id, '$t_a', '$sem', $nf, $nsl, $nsa, $na, $tp_ok, $tp_no, '$desk')
                ON DUPLICATE KEY UPDATE
                kelas_id=$k_id, nilai_formatif=$nf, nilai_sumatif_lingkup=$nsl, nilai_sumatif_akhir=$nsa, nilai_akhir=$na,
                tp_tercapai_id=$tp_ok, tp_perlu_bimbingan_id=$tp_no, deskripsi_capaian='$desk'";
        mysqli_query($conn, $sql);
    }
    header("Location: rekap_nilai.php?kelas_id=$k_id&mapel_id=$m_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=saved");
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="fw-bold mb-0">Input Rekap Nilai & Capaian Kompetensi</h4>
        <small class="text-muted">Input nilai formatif, sumatif, dan deskripsi capaian kompetensi secara manual atau melalui Import CSV/Excel.</small>
    </div>
    <?php if ($kelas_id && $mapel_id): ?>
    <div class="d-flex gap-2">
        <a href="?kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>&download_template=1" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template CSV
        </a>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportNilai">
            <i class="bi bi-upload me-1"></i>Import Nilai (CSV)
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2">
        <?= $_GET['msg'] === 'imported' ? 'Data rekap nilai berhasil diimpor dari file CSV!' : 'Rekap nilai akhir dan deskripsi capaian kompetensi berhasil disimpan!' ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Kelas (Fase D)</label>
                <select name="kelas_id" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php $k=mysqli_query($conn, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas"); while($rk=mysqli_fetch_assoc($k)): ?>
                        <option value="<?= $rk['id'] ?>" <?= $kelas_id==$rk['id']?'selected':'' ?>><?= htmlspecialchars($rk['nama_kelas']) ?> (Tingkat <?= $rk['tingkat'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Mata Pelajaran</label>
                <select name="mapel_id" class="form-select" required>
                    <option value="">-- Pilih Mapel --</option>
                    <?php $m=mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel"); while($rm=mysqli_fetch_assoc($m)): ?>
                        <option value="<?= $rm['id'] ?>" <?= $mapel_id==$rm['id']?'selected':'' ?>><?= htmlspecialchars($rm['nama_mapel']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Semester</label>
                <select name="semester" class="form-select">
                    <option value="1" <?= $semester=='1'?'selected':'' ?>>Ganjil (1)</option>
                    <option value="2" <?= $semester=='2'?'selected':'' ?>>Genap (2)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Tahun Ajaran</label>
                <input type="text" name="ta" class="form-control" value="<?= htmlspecialchars($ta) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($kelas_id && $mapel_id):
    $list_tp = [];
    $q_tp = mysqli_query($conn, "SELECT tp.id, tp.kode_tp, tp.deskripsi_tp FROM tujuan_pembelajaran tp JOIN capaian_pembelajaran cp ON tp.cp_id=cp.id WHERE cp.mapel_id=$mapel_id ORDER BY tp.kode_tp");
    while ($rtp = mysqli_fetch_assoc($q_tp)) {
        $list_tp[] = $rtp;
    }

    $q_siswa = mysqli_query($conn, "
        SELECT s.id as siswa_id, s.nis, s.nama_siswa, rn.*
        FROM siswa s
        LEFT JOIN rekap_nilai rn ON rn.siswa_id = s.id AND rn.mapel_id = $mapel_id AND rn.semester = '$semester' AND rn.tahun_ajaran = '$ta'
        WHERE s.kelas_id = $kelas_id
        ORDER BY s.nama_siswa
    ");
?>
<form method="POST">
    <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
    <input type="hidden" name="mapel_id" value="<?= $mapel_id ?>">
    <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
    <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Lembar Input Rekap Nilai Akhir & Deskripsi e-Rapor</span>
            <span class="badge bg-info text-dark">Formula NA: 30% Formatif + 40% Sumatif Lingkup + 30% Sumatif Akhir</span>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="18%">Nama Siswa / NIS</th>
                        <th width="8%">Formatif (30%)</th>
                        <th width="8%">Sumatif LM (40%)</th>
                        <th width="8%">Sumatif Akhir (30%)</th>
                        <th width="7%">Nilai Rapor</th>
                        <th width="20%">TP Optimal & Perlu Bimbingan</th>
                        <th width="27%">Deskripsi Capaian Kompetensi (Tampil di Rapor)</th>
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
                        <td><input type="number" step="0.01" min="0" max="100" name="nilai_formatif[]" class="form-control form-control-sm text-center" value="<?= $r['nilai_formatif'] ?? 0 ?>" required></td>
                        <td><input type="number" step="0.01" min="0" max="100" name="nilai_sumatif_lingkup[]" class="form-control form-control-sm text-center" value="<?= $r['nilai_sumatif_lingkup'] ?? 0 ?>" required></td>
                        <td><input type="number" step="0.01" min="0" max="100" name="nilai_sumatif_akhir[]" class="form-control form-control-sm text-center" value="<?= $r['nilai_sumatif_akhir'] ?? 0 ?>" required></td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6"><?= $r['nilai_akhir'] ?? 0 ?></span>
                        </td>
                        <td>
                            <select name="tp_tercapai[]" class="form-select form-select-sm mb-1" title="TP yang sudah dikuasai">
                                <option value="">-- TP Tercapai Optimal --</option>
                                <?php foreach ($list_tp as $tp): ?>
                                    <option value="<?= $tp['id'] ?>" <?= ($r['tp_tercapai_id'] ?? '')==$tp['id']?'selected':'' ?>><?= htmlspecialchars($tp['kode_tp']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="tp_bimbingan[]" class="form-select form-select-sm" title="TP yang perlu bimbingan">
                                <option value="">-- TP Perlu Bimbingan --</option>
                                <?php foreach ($list_tp as $tp): ?>
                                    <option value="<?= $tp['id'] ?>" <?= ($r['tp_perlu_bimbingan_id'] ?? '')==$tp['id']?'selected':'' ?>><?= htmlspecialchars($tp['kode_tp']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <textarea name="deskripsi_capaian[]" class="form-control form-control-sm" rows="3" placeholder="Kosongkan untuk generate otomatis dari pilihan TP, atau ketik manual..."><?= htmlspecialchars($r['deskripsi_capaian'] ?? '') ?></textarea>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" name="simpan_nilai" class="btn btn-success px-4"><i class="bi bi-check-circle me-1"></i>Simpan & Hitung Nilai Rapor</button>
            </div>
        </div>
    </div>
</form>

<!-- Modal Import CSV Nilai -->
<div class="modal fade" id="modalImportNilai" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Rekap Nilai (CSV / Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                <input type="hidden" name="mapel_id" value="<?= $mapel_id ?>">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">
                <p class="small text-muted">Unduh terlebih dahulu <strong>Template CSV</strong> agar NIS siswa dan format kolom sesuai, isi nilai di Excel/WPS lalu simpan sebagai <code>.csv</code>.</p>
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
