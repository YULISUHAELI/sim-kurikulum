<?php
require 'config/database.php';
cek_login();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$semester = isset($_GET['semester']) ? esc($_GET['semester']) : '1';
$ta       = isset($_GET['ta']) ? esc($_GET['ta']) : '2026/2027';

// Simpan Data Wali Kelas (Ketidakhadiran, Ekskul, Catatan Wali)
if (isset($_POST['simpan_wali'])) {
    $k_id = (int)$_POST['kelas_id'];
    $sem  = esc($_POST['semester']);
    $t_a  = esc($_POST['ta']);

    foreach ($_POST['siswa_id'] as $idx => $s_id) {
        $s_id  = (int)$s_id;
        $sakit = (int)$_POST['sakit'][$idx];
        $izin  = (int)$_POST['izin'][$idx];
        $alpha = (int)$_POST['alpha'][$idx];
        $ek1_n = esc($_POST['ekskul_1_nama'][$idx]);
        $ek1_p = esc($_POST['ekskul_1_predikat'][$idx]);
        $ek1_k = esc($_POST['ekskul_1_ket'][$idx]);
        $cat   = esc($_POST['catatan_wali'][$idx]);
        $stat  = esc($_POST['status_kenaikan'][$idx]);

        $sql = "INSERT INTO data_rapor_wali (siswa_id, kelas_id, tahun_ajaran, semester, ekskul_1_nama, ekskul_1_predikat, ekskul_1_ket, sakit, izin, alpha, catatan_wali, status_kenaikan)
                VALUES ($s_id, $k_id, '$t_a', '$sem', '$ek1_n', '$ek1_p', '$ek1_k', $sakit, $izin, $alpha, '$cat', '$stat')
                ON DUPLICATE KEY UPDATE
                kelas_id=$k_id, ekskul_1_nama='$ek1_n', ekskul_1_predikat='$ek1_p', ekskul_1_ket='$ek1_k', sakit=$sakit, izin=$izin, alpha=$alpha, catatan_wali='$cat', status_kenaikan='$stat'";
        mysqli_query($conn, $sql);
    }
    header("Location: leger.php?kelas_id=$k_id&semester=$sem&ta=" . urlencode($t_a) . "&msg=saved");
    exit;
}

// Export Leger ke CSV (Excel-friendly)
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $kelas_id) {
    $k_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kelas WHERE id=$kelas_id"));
    $mapels = [];
    $qm = mysqli_query($conn, "SELECT * FROM mapel ORDER BY id");
    while ($rm = mysqli_fetch_assoc($qm)) $mapels[] = $rm;

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Leger_Kelas_' . ($k_info['nama_kelas'] ?? 'SMP') . '_Sem' . $semester . '.csv');
    $out = fopen('php://output', 'w');

    $header = ['No', 'NIS', 'NISN', 'Nama Siswa', 'L/P'];
    foreach ($mapels as $m) $header[] = $m['kode_mapel'];
    $header = array_merge($header, ['Jumlah', 'Rata-rata', 'Sakit', 'Izin', 'Alpha', 'Ekskul', 'Predikat']);
    fputcsv($out, $header);

    $qs = mysqli_query($conn, "SELECT * FROM siswa WHERE kelas_id=$kelas_id ORDER BY nama_siswa");
    $no = 1;
    while ($s = mysqli_fetch_assoc($qs)) {
        $row = [$no++, $s['nis'], $s['nisn'], $s['nama_siswa'], $s['jenis_kelamin']];
        $total = 0; $cnt = 0;
        foreach ($mapels as $m) {
            $qn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai_akhir FROM rekap_nilai WHERE siswa_id={$s['id']} AND mapel_id={$m['id']} AND semester='$semester' AND tahun_ajaran='$ta'"));
            $val = $qn ? (int)$qn['nilai_akhir'] : 0;
            $row[] = $val ?: '-';
            if ($val > 0) { $total += $val; $cnt++; }
        }
        $avg = $cnt > 0 ? round($total / $cnt, 2) : 0;
        $qw = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM data_rapor_wali WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$ta'"));
        $row[] = $total;
        $row[] = $avg;
        $row[] = $qw['sakit'] ?? 0;
        $row[] = $qw['izin'] ?? 0;
        $row[] = $qw['alpha'] ?? 0;
        $row[] = $qw['ekskul_1_nama'] ?? '-';
        $row[] = $qw['ekskul_1_predikat'] ?? '-';
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

include 'includes/header.php';
?>
<!-- Dynamic @page style untuk ukuran F4 (Folio 215mm x 330mm) Landscape / Portrait -->
<style id="dynamicPageStyle">
    @page {
        size: 330mm 215mm; /* Default F4 Landscape */
        margin: 10mm 12mm;
    }
</style>
<style>
    .kop-cetak-leger { display: none; }
    .ttd-cetak-leger { display: none; }

    @media print {
        .no-print, .sidebar, nav, .btn, .alert { display: none !important; }
        body, html { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        main { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { display: none !important; }
        .card-body { padding: 0 !important; overflow: visible !important; }
        .table-responsive { overflow: visible !important; }
        .kop-cetak-leger { display: block !important; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 6px; }
        .ttd-cetak-leger { display: table !important; width: 100%; margin-top: 18px; page-break-inside: avoid; }

        /* Penyesuaian skala tabel F4 Landscape vs Portrait */
        body.print-f4-landscape .table-leger {
            font-size: 8.5pt !important;
            width: 100% !important;
        }
        body.print-f4-landscape .table-leger th,
        body.print-f4-landscape .table-leger td {
            padding: 3px 4px !important;
            border: 1px solid #000 !important;
        }

        body.print-f4-portrait .table-leger {
            font-size: 6.8pt !important;
            width: 100% !important;
            table-layout: fixed;
            word-wrap: break-word;
        }
        body.print-f4-portrait .table-leger th,
        body.print-f4-portrait .table-leger td {
            padding: 2px 2px !important;
            border: 1px solid #000 !important;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
    <div>
        <h4 class="fw-bold mb-0">Leger Nilai Kelas & Data Wali Kelas</h4>
        <small class="text-muted">Rekapitulasi nilai seluruh mata pelajaran, absensi, serta cetak leger kertas F4 (Landscape / Portrait).</small>
    </div>
    <?php if ($kelas_id): ?>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="?kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>&export=csv" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Export CSV
        </a>
        <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text bg-light fw-semibold"><i class="bi bi-file-earmark-text me-1"></i>Kertas F4</span>
            <select id="orientasiF4" class="form-select form-select-sm" onchange="setOrientasiF4()">
                <option value="landscape" selected>Landscape (330 x 215 mm)</option>
                <option value="portrait">Portrait (215 x 330 mm)</option>
            </select>
            <button type="button" onclick="cetakLegerF4()" class="btn btn-primary btn-sm">
                <i class="bi bi-printer-fill me-1"></i>Cetak Leger F4
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success py-2 no-print">Data kehadiran, ekstrakurikuler, dan catatan wali kelas berhasil disimpan!</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4 no-print">
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
                <button type="submit" class="btn btn-primary w-100">Tampilkan Leger</button>
            </div>
        </form>
    </div>
</div>

<?php if ($kelas_id):
    $sekolah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM identitas_sekolah LIMIT 1"));
    $k_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT k.*, g.nama_guru as wali, g.nip as nip_wali FROM kelas k LEFT JOIN guru g ON k.wali_kelas_id=g.id WHERE k.id=$kelas_id"));
    $mapels = [];
    $qm = mysqli_query($conn, "SELECT * FROM mapel ORDER BY id");
    while ($rm = mysqli_fetch_assoc($qm)) $mapels[] = $rm;

    $siswas = [];
    $qs = mysqli_query($conn, "SELECT * FROM siswa WHERE kelas_id=$kelas_id ORDER BY nama_siswa");
    while ($rs = mysqli_fetch_assoc($qs)) $siswas[] = $rs;
?>

<!-- Kop Cetak Khusus Leger F4 -->
<div class="kop-cetak-leger text-center">
    <div class="fw-bold text-uppercase" style="font-size: 12pt;">LEGER NILAI HASIL BELAJAR PESERTA DIDIK (KURIKULUM MERDEKA — FASE D)</div>
    <div class="fw-bold" style="font-size: 11pt;"><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'SMP NEGERI') ?></div>
    <div style="font-size: 9.5pt;">
        Kelas: <strong><?= htmlspecialchars($k_info['nama_kelas']) ?></strong> &bull;
        Semester: <strong><?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></strong> &bull;
        Tahun Pelajaran: <strong><?= htmlspecialchars($ta) ?></strong> &bull;
        Wali Kelas: <strong><?= htmlspecialchars($k_info['wali'] ?? '-') ?></strong>
    </div>
</div>

<!-- Tabel Leger Nilai -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-bold">LEGER NILAI KELAS <?= htmlspecialchars($k_info['nama_kelas']) ?> — SEMESTER <?= $semester ?> (TA <?= htmlspecialchars($ta) ?>)</div>
            <small class="text-muted">Wali Kelas: <?= htmlspecialchars($k_info['wali'] ?? '-') ?></small>
        </div>
        <span class="badge bg-info text-dark" id="badgeOrientasi">Format Cetak: F4 Landscape</span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-sm table-hover align-middle text-center table-leger">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">No</th>
                    <th rowspan="2" style="width: 7%;">NIS</th>
                    <th rowspan="2" class="text-start" style="width: 18%;">Nama Peserta Didik</th>
                    <th rowspan="2" style="width: 3%;">L/P</th>
                    <th colspan="<?= count($mapels) ?>">Nilai Akhir Mata Pelajaran</th>
                    <th rowspan="2" style="width: 6%;">Jumlah</th>
                    <th rowspan="2" style="width: 6%;">Rata2</th>
                    <th colspan="3">Absensi</th>
                </tr>
                <tr>
                    <?php foreach ($mapels as $m): ?>
                        <th title="<?= htmlspecialchars($m['nama_mapel']) ?>"><?= htmlspecialchars($m['kode_mapel']) ?></th>
                    <?php endforeach; ?>
                    <th style="width: 3%;">S</th>
                    <th style="width: 3%;">I</th>
                    <th style="width: 3%;">A</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach ($siswas as $s):
                    $total = 0; $cnt = 0;
                    $qw = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM data_rapor_wali WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$ta'"));
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($s['nis']) ?></td>
                    <td class="text-start fw-semibold"><?= htmlspecialchars($s['nama_siswa']) ?></td>
                    <td><?= $s['jenis_kelamin'] ?></td>
                    <?php foreach ($mapels as $m):
                        $qn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai_akhir FROM rekap_nilai WHERE siswa_id={$s['id']} AND mapel_id={$m['id']} AND semester='$semester' AND tahun_ajaran='$ta'"));
                        $val = $qn ? (int)$qn['nilai_akhir'] : 0;
                        if ($val > 0) { $total += $val; $cnt++; }
                    ?>
                        <td><?= $val > 0 ? $val : '<span class="text-muted">-</span>' ?></td>
                    <?php endforeach; ?>
                    <td class="fw-bold bg-light"><?= $total ?></td>
                    <td class="fw-bold text-primary"><?= $cnt > 0 ? number_format($total/$cnt, 1) : '0.0' ?></td>
                    <td><?= $qw['sakit'] ?? 0 ?></td>
                    <td><?= $qw['izin'] ?? 0 ?></td>
                    <td><?= $qw['alpha'] ?? 0 ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Kolom Pengesahan Cetak Leger F4 -->
<table class="ttd-cetak-leger" style="font-size: 9.5pt;">
    <tr>
        <td width="40%" class="text-center">
            Mengetahui,<br>Kepala Sekolah
            <br><br><br><br>
            <u><strong><?= htmlspecialchars($sekolah['nama_kepsek'] ?? '........................................') ?></strong></u><br>
            NIP. <?= htmlspecialchars($sekolah['nip_kepsek'] ?? '-') ?>
        </td>
        <td width="20%"></td>
        <td width="40%" class="text-center">
            <?= htmlspecialchars($sekolah['tempat_cetak'] ?? 'Bandung') ?>, <?= htmlspecialchars($sekolah['tanggal_rapor'] ?? '20 Desember 2026') ?><br>
            Wali Kelas <?= htmlspecialchars($k_info['nama_kelas']) ?>,
            <br><br><br><br>
            <u><strong><?= htmlspecialchars($k_info['wali'] ?? '........................................') ?></strong></u><br>
            NIP. <?= htmlspecialchars($k_info['nip_wali'] ?? '-') ?>
        </td>
    </tr>
</table>

<!-- Form Input Pelengkap Rapor oleh Wali Kelas -->
<div class="card border-0 shadow-sm no-print">
    <div class="card-header bg-white fw-bold">Input Cepat Ekstrakurikuler, Ketidakhadiran & Catatan Wali Kelas</div>
    <div class="card-body table-responsive">
        <form method="POST">
            <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
            <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
            <input type="hidden" name="ta" value="<?= htmlspecialchars($ta) ?>">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="18%">Nama Siswa</th>
                        <th width="22%">Ekstrakurikuler & Predikat</th>
                        <th width="15%">Ketidakhadiran (S/I/A)</th>
                        <th width="28%">Catatan Wali Kelas</th>
                        <th width="17%">Keterangan Kenaikan / Kelulusan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siswas as $s):
                        $qw = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM data_rapor_wali WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$ta'"));
                    ?>
                    <tr>
                        <td>
                            <input type="hidden" name="siswa_id[]" value="<?= $s['id'] ?>">
                            <strong><?= htmlspecialchars($s['nama_siswa']) ?></strong>
                        </td>
                        <td>
                            <input type="text" name="ekskul_1_nama[]" class="form-control form-control-sm mb-1" placeholder="Nama Ekskul (mis: Pramuka)" value="<?= htmlspecialchars($qw['ekskul_1_nama'] ?? 'Pramuka') ?>">
                            <select name="ekskul_1_predikat[]" class="form-select form-select-sm mb-1">
                                <option value="Sangat Baik" <?= ($qw['ekskul_1_predikat'] ?? '')=='Sangat Baik'?'selected':'' ?>>Sangat Baik</option>
                                <option value="Baik" <?= ($qw['ekskul_1_predikat'] ?? '')=='Baik'?'selected':'' ?>>Baik</option>
                                <option value="Cukup" <?= ($qw['ekskul_1_predikat'] ?? '')=='Cukup'?'selected':'' ?>>Cukup</option>
                            </select>
                            <input type="text" name="ekskul_1_ket[]" class="form-control form-control-sm" placeholder="Keterangan kegiatan..." value="<?= htmlspecialchars($qw['ekskul_1_ket'] ?? '') ?>">
                        </td>
                        <td>
                            <div class="input-group input-group-sm mb-1"><span class="input-group-text">S</span><input type="number" name="sakit[]" class="form-control" value="<?= $qw['sakit'] ?? 0 ?>"></div>
                            <div class="input-group input-group-sm mb-1"><span class="input-group-text">I</span><input type="number" name="izin[]" class="form-control" value="<?= $qw['izin'] ?? 0 ?>"></div>
                            <div class="input-group input-group-sm"><span class="input-group-text">A</span><input type="number" name="alpha[]" class="form-control" value="<?= $qw['alpha'] ?? 0 ?>"></div>
                        </td>
                        <td>
                            <textarea name="catatan_wali[]" class="form-control form-control-sm" rows="3" placeholder="Catatan motivasi dari wali kelas..."><?= htmlspecialchars($qw['catatan_wali'] ?? '') ?></textarea>
                        </td>
                        <td>
                            <input type="text" name="status_kenaikan[]" class="form-control form-control-sm" value="<?= htmlspecialchars($qw['status_kenaikan'] ?? ($semester=='1' ? 'Lanjut ke Semester Genap' : 'Naik ke Kelas Berikutnya')) ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-end">
                <button type="submit" name="simpan_wali" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Data Wali Kelas</button>
            </div>
        </form>
    </div>
</div>

<script>
function setOrientasiF4() {
    const mode = document.getElementById('orientasiF4').value;
    const styleEl = document.getElementById('dynamicPageStyle');
    const badgeEl = document.getElementById('badgeOrientasi');

    document.body.classList.remove('print-f4-landscape', 'print-f4-portrait');

    if (mode === 'portrait') {
        styleEl.innerHTML = '@page { size: 215mm 330mm; margin: 10mm 8mm; }';
        document.body.classList.add('print-f4-portrait');
        if (badgeEl) badgeEl.textContent = 'Format Cetak: F4 Portrait (215 x 330 mm)';
    } else {
        styleEl.innerHTML = '@page { size: 330mm 215mm; margin: 10mm 12mm; }';
        document.body.classList.add('print-f4-landscape');
        if (badgeEl) badgeEl.textContent = 'Format Cetak: F4 Landscape (330 x 215 mm)';
    }
}

function cetakLegerF4() {
    setOrientasiF4();
    window.print();
}

// Inisialisasi default F4 Landscape saat halaman dimuat
document.addEventListener('DOMContentLoaded', setOrientasiF4);
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
