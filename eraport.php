<?php
require 'config/database.php';
cek_login();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$semester = isset($_GET['semester']) ? esc($_GET['semester']) : '1';
$ta       = isset($_GET['ta']) ? esc($_GET['ta']) : '2026/2027';

// Fungsi pengurutan mata pelajaran sesuai urutan baku e-Rapor Kemendikbudristek (Kurikulum Merdeka SMP)
function get_urutan_kemendikbud($nama_mapel, $kode_mapel = '', $kelompok = '') {
    $n = strtolower(trim($nama_mapel));
    $k = strtolower(trim($kode_mapel));
    $kel = strtolower(trim($kelompok));

    if (strpos($n, 'agama') !== false || strpos($k, 'pa') === 0 || strpos($k, 'pabp') !== false) return 1;
    if (strpos($n, 'pancasila') !== false || strpos($n, 'ppkn') !== false || $k === 'pp' || $k === 'ppkn') return 2;
    if (strpos($n, 'bahasa indonesia') !== false || $k === 'bin' || $k === 'bind') return 3;
    if (strpos($n, 'matematika') !== false || $k === 'mtk' || $k === 'mat') return 4;
    if (strpos($n, 'pengetahuan alam') !== false || $n === 'ipa' || $k === 'ipa') return 5;
    if (strpos($n, 'pengetahuan sosial') !== false || $n === 'ips' || $k === 'ips') return 6;
    if (strpos($n, 'bahasa inggris') !== false || $k === 'big' || $k === 'bing') return 7;
    if (strpos($n, 'jasmani') !== false || strpos($n, 'pjok') !== false || strpos($n, 'penjas') !== false || $k === 'pjok') return 8;
    if (strpos($n, 'informatika') !== false || strpos($n, 'tik') !== false || $k === 'inf' || $k === 'tik') return 9;
    if (strpos($n, 'seni') !== false || strpos($n, 'prakarya') !== false || $k === 'sbk' || $k === 'sbdp' || $k === 'pkw') return 10;
    if (strpos($kel, 'pilihan') !== false) return 11;
    if (strpos($kel, 'muatan lokal') !== false || strpos($kel, 'mulok') !== false || strpos($n, 'daerah') !== false || strpos($n, 'sunda') !== false || strpos($n, 'jawa') !== false || $k === 'mulok') return 20;
    if (strpos($kel, 'p5') !== false || strpos($n, 'projek') !== false) return 30;
    return 15;
}

function ambil_nilai_terurut($conn, $siswa_id, $semester, $ta) {
    $q = mysqli_query($conn, "
        SELECT m.id, m.kode_mapel, m.nama_mapel, m.kelompok, rn.nilai_akhir, rn.deskripsi_capaian
        FROM mapel m
        LEFT JOIN rekap_nilai rn ON rn.mapel_id = m.id AND rn.siswa_id = $siswa_id AND rn.semester = '$semester' AND rn.tahun_ajaran = '$ta'
    ");
    $list = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $row['urutan_baku'] = get_urutan_kemendikbud($row['nama_mapel'], $row['kode_mapel'], $row['kelompok']);
        $list[] = $row;
    }
    usort($list, function($a, $b) {
        if ($a['urutan_baku'] === $b['urutan_baku']) {
            return strcmp($a['nama_mapel'], $b['nama_mapel']);
        }
        return $a['urutan_baku'] <=> $b['urutan_baku'];
    });
    return $list;
}

function render_lembar_rapor($conn, $siswa, $sekolah, $semester, $ta) {
    $siswa_id  = (int)$siswa['id'];
    $wali_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM data_rapor_wali WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$ta'"));
    $daftar_nilai = ambil_nilai_terurut($conn, $siswa_id, $semester, $ta);

    $mapel_umum  = [];
    $mapel_mulok = [];
    foreach ($daftar_nilai as $item) {
        $kel = strtolower(trim($item['kelompok'] ?? ''));
        $nm  = strtolower(trim($item['nama_mapel'] ?? ''));
        if (strpos($kel, 'muatan lokal') !== false || strpos($kel, 'mulok') !== false || strpos($nm, 'daerah') !== false || strpos($nm, 'sunda') !== false || strpos($nm, 'jawa') !== false) {
            $mapel_mulok[] = $item;
        } else {
            $mapel_umum[] = $item;
        }
    }
    ?>
    <div class="page-rapor-f4">
        <div class="text-center mb-3">
            <h5 class="fw-bold text-uppercase mb-0" style="letter-spacing: 0.6px; font-size: 13pt;">LAPORAN HASIL BELAJAR (e-RAPOR)</h5>
            <div style="font-size: 10.5pt;">KURIKULUM MERDEKA — JENJANG SEKOLAH MENENGAH PERTAMA (SMP)</div>
        </div>

        <!-- Identitas Peserta Didik & Satuan Pendidikan (Standar Kemendikbudristek) -->
        <table class="w-100 identitas-table mb-3" style="font-size: 10.5pt;">
            <tr>
                <td width="19%">Nama Peserta Didik</td>
                <td width="2%">:</td>
                <td width="37%" class="fw-bold text-uppercase"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                <td width="17%">Kelas</td>
                <td width="2%">:</td>
                <td width="23%"><?= htmlspecialchars($siswa['nama_kelas']) ?></td>
            </tr>
            <tr>
                <td>NIS / NISN</td>
                <td>:</td>
                <td><?= htmlspecialchars($siswa['nis']) ?> / <?= htmlspecialchars($siswa['nisn']) ?></td>
                <td>Fase</td>
                <td>:</td>
                <td><strong>D</strong></td>
            </tr>
            <tr>
                <td>Nama Sekolah</td>
                <td>:</td>
                <td><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'SMP Negeri') ?></td>
                <td>Semester</td>
                <td>:</td>
                <td><?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></td>
            </tr>
            <tr>
                <td>Alamat Sekolah</td>
                <td>:</td>
                <td><?= htmlspecialchars($sekolah['alamat'] ?? '-') ?></td>
                <td>Tahun Pelajaran</td>
                <td>:</td>
                <td><?= htmlspecialchars($ta) ?></td>
            </tr>
        </table>

        <!-- A. Nilai dan Capaian Kompetensi -->
        <div class="section-title">A. Nilai dan Capaian Kompetensi</div>
        <table class="table-rapor w-100 mb-3">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="30%">Mata Pelajaran</th>
                    <th width="11%">Nilai Akhir</th>
                    <th width="54%">Capaian Kompetensi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($mapel_umum as $rn): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($rn['nama_mapel']) ?></td>
                    <td class="text-center fw-bold"><?= $rn['nilai_akhir'] !== null ? $rn['nilai_akhir'] : '-' ?></td>
                    <td class="desc-cell"><?= $rn['deskripsi_capaian'] ? nl2br(htmlspecialchars($rn['deskripsi_capaian'])) : '<span class="text-muted">-</span>' ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if (!empty($mapel_mulok)): ?>
                <tr>
                    <td colspan="4" class="fw-bold bg-subhead">Muatan Lokal</td>
                </tr>
                <?php $no_ml = 1; foreach ($mapel_mulok as $rn): ?>
                <tr>
                    <td class="text-center"><?= $no_ml++ ?></td>
                    <td><?= htmlspecialchars($rn['nama_mapel']) ?></td>
                    <td class="text-center fw-bold"><?= $rn['nilai_akhir'] !== null ? $rn['nilai_akhir'] : '-' ?></td>
                    <td class="desc-cell"><?= $rn['deskripsi_capaian'] ? nl2br(htmlspecialchars($rn['deskripsi_capaian'])) : '<span class="text-muted">-</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- B. Ekstrakurikuler -->
        <div class="section-title">B. Ekstrakurikuler</div>
        <table class="table-rapor w-100 mb-3">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="30%">Kegiatan Ekstrakurikuler</th>
                    <th width="15%">Predikat</th>
                    <th width="50%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $ada_ekskul = false;
                $no_ek = 1;
                if (!empty($wali_data['ekskul_1_nama'])):
                    $ada_ekskul = true;
                ?>
                <tr>
                    <td class="text-center"><?= $no_ek++ ?></td>
                    <td><?= htmlspecialchars($wali_data['ekskul_1_nama']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($wali_data['ekskul_1_predikat']) ?></td>
                    <td><?= htmlspecialchars($wali_data['ekskul_1_ket']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($wali_data['ekskul_2_nama'])):
                    $ada_ekskul = true;
                ?>
                <tr>
                    <td class="text-center"><?= $no_ek++ ?></td>
                    <td><?= htmlspecialchars($wali_data['ekskul_2_nama']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($wali_data['ekskul_2_predikat']) ?></td>
                    <td><?= htmlspecialchars($wali_data['ekskul_2_ket']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!$ada_ekskul): ?>
                <tr>
                    <td class="text-center">1</td>
                    <td>-</td>
                    <td class="text-center">-</td>
                    <td>-</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- C. Ketidakhadiran & D. Catatan Wali Kelas -->
        <div class="avoid-break">
            <table class="w-100 mb-3" style="border: none; border-collapse: collapse;">
                <tr>
                    <td width="38%" style="vertical-align: top; padding-right: 10px;">
                        <div class="section-title">C. Ketidakhadiran</div>
                        <table class="table-rapor w-100">
                            <tr>
                                <td width="62%">Sakit</td>
                                <td width="38%"><?= (int)($wali_data['sakit'] ?? 0) ?> hari</td>
                            </tr>
                            <tr>
                                <td>Izin</td>
                                <td><?= (int)($wali_data['izin'] ?? 0) ?> hari</td>
                            </tr>
                            <tr>
                                <td>Tanpa Keterangan</td>
                                <td><?= (int)($wali_data['alpha'] ?? 0) ?> hari</td>
                            </tr>
                        </table>
                    </td>
                    <td width="62%" style="vertical-align: top; padding-left: 6px;">
                        <div class="section-title">D. Catatan Wali Kelas</div>
                        <div class="catatan-box">
                            <?= nl2br(htmlspecialchars($wali_data['catatan_wali'] ?? 'Terus tingkatkan motivasi belajar dan karakter Profil Pelajar Pancasila.')) ?>
                        </div>
                    </td>
                </tr>
            </table>

            <?php if (!empty($wali_data['status_kenaikan'])): ?>
            <div class="keputusan-box mb-3">
                <strong>Keputusan:</strong> <?= htmlspecialchars($wali_data['status_kenaikan']) ?>
            </div>
            <?php endif; ?>

            <!-- Kolom Pengesahan Resmi Kemendikbud -->
            <table class="w-100 mt-3 ttd-table">
                <tr>
                    <td width="35%" class="text-center">
                        Mengetahui,<br>Orang Tua / Wali
                        <div class="ttd-space"></div>
                        ( ........................................ )
                    </td>
                    <td width="30%"></td>
                    <td width="35%" class="text-center">
                        <?= htmlspecialchars($sekolah['tempat_cetak'] ?? 'Bandung') ?>, <?= htmlspecialchars($sekolah['tanggal_rapor'] ?? '20 Desember 2026') ?><br>
                        Wali Kelas,
                        <div class="ttd-space"></div>
                        <u><strong><?= htmlspecialchars($siswa['wali_kelas'] ?? '........................................') ?></strong></u><br>
                        NIP. <?= htmlspecialchars($siswa['nip_wali'] ?? '-') ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="text-center pt-2">
                        Mengetahui,<br>Kepala Sekolah
                        <div class="ttd-space"></div>
                        <u><strong><?= htmlspecialchars($sekolah['nama_kepsek'] ?? '........................................') ?></strong></u><br>
                        NIP. <?= htmlspecialchars($sekolah['nip_kepsek'] ?? '-') ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

// Mode Cetak: Per Siswa ATAU Keseluruhan Satu Kelas (Ukuran Kertas F4: 215mm x 330mm)
$is_cetak_siswa = isset($_GET['cetak_siswa']);
$is_cetak_kelas = isset($_GET['cetak_kelas']) && $kelas_id > 0;

if ($is_cetak_siswa || $is_cetak_kelas) {
    $sekolah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM identitas_sekolah LIMIT 1"));
    $daftar_siswa_cetak = [];

    if ($is_cetak_siswa) {
        $sid = (int)$_GET['cetak_siswa'];
        $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, k.nama_kelas, k.tingkat, g.nama_guru as wali_kelas, g.nip as nip_wali FROM siswa s LEFT JOIN kelas k ON s.kelas_id=k.id LEFT JOIN guru g ON k.wali_kelas_id=g.id WHERE s.id=$sid"));
        if (!$s) die("Data siswa tidak ditemukan.");
        $daftar_siswa_cetak[] = $s;
        $back_kelas_id = (int)$s['kelas_id'];
        $doc_title = "e-Rapor KM (F4) - " . $s['nama_siswa'];
    } else {
        $qs = mysqli_query($conn, "SELECT s.*, k.nama_kelas, k.tingkat, g.nama_guru as wali_kelas, g.nip as nip_wali FROM siswa s LEFT JOIN kelas k ON s.kelas_id=k.id LEFT JOIN guru g ON k.wali_kelas_id=g.id WHERE s.kelas_id=$kelas_id ORDER BY s.nama_siswa");
        while ($row_s = mysqli_fetch_assoc($qs)) {
            $daftar_siswa_cetak[] = $row_s;
        }
        if (empty($daftar_siswa_cetak)) die("Belum ada peserta didik pada kelas yang dipilih.");
        $back_kelas_id = $kelas_id;
        $doc_title = "e-Rapor Kelas " . ($daftar_siswa_cetak[0]['nama_kelas'] ?? '') . " (F4)";
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($doc_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Pengaturan Kertas F4 / Folio (215mm x 330mm) */
        @page {
            size: 215mm 330mm;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            color: #000;
            background: #e2e8f0;
            margin: 0;
            padding: 0;
        }
        .toolbar-cetak {
            background: #0f172a;
            color: #fff;
            padding: 10px 20px;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .page-rapor-f4 {
            background: #fff;
            width: 215mm;
            min-height: 330mm;
            margin: 16px auto;
            padding: 13mm 16mm;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            box-sizing: border-box;
            page-break-after: always;
            break-after: page;
        }
        .page-rapor-f4:last-child {
            page-break-after: auto;
            break-after: auto;
        }
        .section-title {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 4px;
        }
        .identitas-table td {
            padding: 2px 4px;
            border: none !important;
            line-height: 1.3;
        }
        .table-rapor {
            border-collapse: collapse;
            font-size: 10pt;
        }
        .table-rapor th, .table-rapor td {
            border: 1px solid #000 !important;
            padding: 5px 7px;
            vertical-align: middle;
            line-height: 1.3;
        }
        .table-rapor td.desc-cell {
            vertical-align: top;
            text-align: justify;
        }
        .table-rapor th {
            background-color: #f1f5f9 !important;
            text-align: center;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .bg-subhead {
            background-color: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .catatan-box {
            border: 1px solid #000;
            padding: 7px 9px;
            min-height: 82px;
            font-size: 10pt;
            line-height: 1.35;
        }
        .keputusan-box {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 10pt;
        }
        .ttd-table {
            font-size: 10pt;
            border: none;
        }
        .ttd-table td {
            border: none !important;
            vertical-align: top;
        }
        .ttd-space {
            height: 52px;
        }
        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        @media print {
            body {
                background: #fff !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .page-rapor-f4 {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>

<div class="toolbar-cetak d-flex justify-content-between align-items-center no-print">
    <div>
        <strong>Pratinjau Cetak e-Rapor Kemendikbud (Kertas F4: 215 × 330 mm)</strong>
        <span class="badge bg-info text-dark ms-2"><?= count($daftar_siswa_cetak) ?> Peserta Didik</span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
            <i class="bi bi-printer-fill me-1"></i>Cetak Sekarang (Kertas F4)
        </button>
        <a href="eraport.php?kelas_id=<?= $back_kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<?php foreach ($daftar_siswa_cetak as $siswa_item): ?>
    <?php render_lembar_rapor($conn, $siswa_item, $sekolah, $semester, $ta); ?>
<?php endforeach; ?>

</body>
</html>
<?php
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">Cetak e-Rapor Kurikulum Merdeka (Fase D — Kertas F4)</h4>
        <small class="text-muted">Urutan mata pelajaran disusun otomatis sesuai standar e-Rapor Kemendikbudristek untuk ukuran kertas F4 (215 × 330 mm).</small>
    </div>
    <?php if ($kelas_id): ?>
    <div>
        <a href="?cetak_kelas=1&kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" target="_blank" class="btn btn-success">
            <i class="bi bi-printer-fill me-1"></i>Cetak Semua Rapor Kelas Ini (F4)
        </a>
    </div>
    <?php endif; ?>
</div>

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
                <button type="submit" class="btn btn-primary w-100">Tampilkan Siswa</button>
            </div>
        </form>
    </div>
</div>

<?php if ($kelas_id):
    $k_sel = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kelas WHERE id=$kelas_id"));
    $q_s = mysqli_query($conn, "
        SELECT s.*,
        (SELECT COUNT(*) FROM rekap_nilai rn WHERE rn.siswa_id=s.id AND rn.semester='$semester' AND rn.tahun_ajaran='$ta') as mapel_terisi
        FROM siswa s
        WHERE s.kelas_id=$kelas_id
        ORDER BY s.nama_siswa
    ");
    $total_mapel = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM mapel"))[0];
?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Daftar Peserta Didik Kelas <?= htmlspecialchars($k_sel['nama_kelas'] ?? '') ?> Siap Cetak e-Rapor (F4)</span>
        <a href="?cetak_kelas=1&kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" target="_blank" class="btn btn-sm btn-success">
            <i class="bi bi-printer-fill me-1"></i>Cetak Seluruh Siswa Kelas <?= htmlspecialchars($k_sel['nama_kelas'] ?? '') ?> (F4)
        </a>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="18%">NIS / NISN</th>
                    <th width="32%">Nama Peserta Didik</th>
                    <th width="10%">L/P</th>
                    <th width="18%">Kelengkapan Nilai</th>
                    <th width="17%">Aksi Cetak</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while ($r = mysqli_fetch_assoc($q_s)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="small"><?= htmlspecialchars($r['nis']) ?> / <?= htmlspecialchars($r['nisn']) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['nama_siswa']) ?></td>
                    <td><?= $r['jenis_kelamin'] ?></td>
                    <td>
                        <span class="badge <?= $r['mapel_terisi'] >= $total_mapel ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= $r['mapel_terisi'] ?> / <?= $total_mapel ?> Mapel
                        </span>
                    </td>
                    <td>
                        <a href="?cetak_siswa=<?= $r['id'] ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" target="_blank" class="btn btn-sm btn-primary">
                            <i class="bi bi-printer-fill me-1"></i>Cetak Rapor (F4)
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
