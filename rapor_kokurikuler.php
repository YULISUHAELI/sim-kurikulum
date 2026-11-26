<?php
require 'config/database.php';
cek_login();

$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$semester = isset($_GET['semester']) ? esc($_GET['semester']) : '1';
$ta       = isset($_GET['ta']) ? esc($_GET['ta']) : '2026/2027';

function deskripsi_kebiasaan($nama_kebiasaan, $level) {
    $map_ket = [
        'Membudaya' => "Ananda telah konsisten dan mandiri membiasakan {$nama_kebiasaan} secara berkelanjutan baik di sekolah maupun di rumah.",
        'Berkembang' => "Ananda mampu melaksanakan pembiasaan {$nama_kebiasaan} dengan baik dan terus menunjukkan peningkatan konsistensi.",
        'Mulai Berkembang' => "Ananda mulai membiasakan {$nama_kebiasaan} dengan arahan serta motivasi dari guru dan orang tua.",
        'Perlu Bimbingan' => "Ananda masih memerlukan pendampingan dan pembiasaan intensif dalam menerapkan {$nama_kebiasaan}."
    ];
    return $map_ket[$level] ?? $map_ket['Berkembang'];
}

// Mode Cetak Rapor Kokurikuler 7KAIH (Terpisah dari e-Rapor KM)
if (isset($_GET['cetak_siswa'])) {
    $siswa_id = (int)$_GET['cetak_siswa'];
    $sekolah  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM identitas_sekolah LIMIT 1"));
    $siswa    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, k.nama_kelas, k.tingkat, g.nama_guru as wali_kelas, g.nip as nip_wali FROM siswa s LEFT JOIN kelas k ON s.kelas_id=k.id LEFT JOIN guru g ON k.wali_kelas_id=g.id WHERE s.id=$siswa_id"));

    if (!$siswa) die("Data siswa tidak ditemukan.");

    $kk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nilai_kokurikuler_7kaih WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$ta'"));
    $daftar_7kaih = [
        ['no' => 1, 'judul' => 'Bangun Pagi', 'desc_singkat' => 'Melatih kedisiplinan waktu dan kesiapan fisik-mental memulai aktivitas belajar.', 'val' => $kk['bangun_pagi'] ?? 'Berkembang', 'label' => 'bangun pagi'],
        ['no' => 2, 'judul' => 'Beribadah', 'desc_singkat' => 'Menjalankan ibadah sesuai agama dan kepercayaan secara tertib serta berakhlak mulia.', 'val' => $kk['beribadah'] ?? 'Berkembang', 'label' => 'beribadah tepat waktu'],
        ['no' => 3, 'judul' => 'Berolahraga', 'desc_singkat' => 'Menjaga kebugaran jasmani melalui aktivitas fisik, senam, dan olahraga teratur.', 'val' => $kk['berolahraga'] ?? 'Berkembang', 'label' => 'berolahraga secara teratur'],
        ['no' => 4, 'judul' => 'Makan Sehat dan Bergizi', 'desc_singkat' => 'Membiasakan pola konsumsi makanan bergizi seimbang serta menjaga kebersihan diri.', 'val' => $kk['makan_sehat'] ?? 'Berkembang', 'label' => 'makan sehat dan bergizi'],
        ['no' => 5, 'judul' => 'Gemar Belajar', 'desc_singkat' => 'Menumbuhkan rasa ingin tahu, literasi membaca, dan semangat belajar sepanjang hayat.', 'val' => $kk['gemar_belajar'] ?? 'Berkembang', 'label' => 'gemar belajar'],
        ['no' => 6, 'judul' => 'Bermasyarakat', 'desc_singkat' => 'Berpartisipasi aktif dalam kegiatan sosial, gotong royong, dan peduli lingkungan sekitar.', 'val' => $kk['bermasyarakat'] ?? 'Berkembang', 'label' => 'bermasyarakat dan bergotong royong'],
        ['no' => 7, 'judul' => 'Tidur Cepat', 'desc_singkat' => 'Mengatur waktu istirahat malam secara teratur demi kesehatan dan konsentrasi belajar.', 'val' => $kk['tidur_cepat'] ?? 'Berkembang', 'label' => 'tidur cepat dan teratur'],
    ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Kokurikuler 7KAIH - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; background: #eee; }
        .page-rapor { background: #fff; width: 210mm; min-height: 297mm; margin: 15px auto; padding: 14mm 18mm; box-shadow: 0 0 10px rgba(0,0,0,0.15); }
        .kop-header { border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
        .table-rapor th, .table-rapor td { border: 1px solid #000 !important; padding: 6px 8px; vertical-align: middle; }
        .table-rapor th { background-color: #e2e8f0 !important; text-align: center; font-weight: bold; }
        .identitas-table td { padding: 2px 4px; border: none !important; }
        .badge-capaian { display: inline-block; padding: 3px 8px; border: 1px solid #000; font-weight: bold; font-size: 9.5pt; border-radius: 4px; }
        @media print {
            body { background: #fff; margin: 0; }
            .page-rapor { width: 100%; margin: 0; padding: 10mm 12mm; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container text-center my-3 no-print">
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Cetak / Simpan PDF Rapor Kokurikuler 7KAIH</button>
    <a href="rapor_kokurikuler.php?kelas_id=<?= $siswa['kelas_id'] ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" class="btn btn-secondary">Kembali ke Daftar</a>
</div>

<div class="page-rapor">
    <div class="kop-header text-center">
        <div class="fw-bold text-uppercase" style="font-size: 13pt;">LAPORAN CAPAIAN KOKURIKULER</div>
        <div class="fw-bold text-uppercase" style="font-size: 12pt;">GERAKAN 7 KEBIASAAN ANAK INDONESIA HEBAT (7KAIH)</div>
        <div style="font-size: 10.5pt;"><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'SMP NEGERI') ?> — JENJANG SMP (FASE D)</div>
    </div>

    <!-- Identitas Peserta Didik -->
    <table class="w-100 identitas-table mb-3" style="font-size: 10.5pt;">
        <tr>
            <td width="19%">Nama Peserta Didik</td>
            <td width="2%">:</td>
            <td width="36%" class="fw-bold"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
            <td width="17%">Kelas / Fase</td>
            <td width="2%">:</td>
            <td width="24%"><?= htmlspecialchars($siswa['nama_kelas']) ?> / <strong>D</strong></td>
        </tr>
        <tr>
            <td>NIS / NISN</td>
            <td>:</td>
            <td><?= htmlspecialchars($siswa['nis']) ?> / <?= htmlspecialchars($siswa['nisn']) ?></td>
            <td>Semester</td>
            <td>:</td>
            <td><?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></td>
        </tr>
        <tr>
            <td>Satuan Pendidikan</td>
            <td>:</td>
            <td><?= htmlspecialchars($sekolah['nama_sekolah'] ?? 'SMP Negeri') ?></td>
            <td>Tahun Pelajaran</td>
            <td>:</td>
            <td><?= htmlspecialchars($ta) ?></td>
        </tr>
    </table>

    <!-- Tabel Capaian 7 Kebiasaan Anak Indonesia Hebat -->
    <p class="fw-bold mb-1">A. Capaian Pembiasaan 7 Kebiasaan Anak Indonesia Hebat (7KAIH)</p>
    <table class="table table-rapor w-100 mb-3" style="font-size: 10pt;">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Dimensi Kebiasaan (7KAIH)</th>
                <th width="20%">Tingkat Pembiasaan</th>
                <th width="50%">Deskripsi Capaian Perkembangan Karakter</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($daftar_7kaih as $item): ?>
            <tr>
                <td class="text-center"><?= $item['no'] ?></td>
                <td>
                    <strong><?= htmlspecialchars($item['judul']) ?></strong><br>
                    <span style="font-size: 8.8pt; color: #333;"><?= htmlspecialchars($item['desc_singkat']) ?></span>
                </td>
                <td class="text-center">
                    <span class="badge-capaian"><?= htmlspecialchars($item['val']) ?></span>
                </td>
                <td style="text-align: justify;">
                    <?= htmlspecialchars(deskripsi_kebiasaan($item['label'], $item['val'])) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Ringkasan Predikat & Catatan Kokurikuler -->
    <p class="fw-bold mb-1">B. Predikat & Catatan Perkembangan Kokurikuler</p>
    <table class="table table-rapor w-100 mb-3" style="font-size: 10.5pt;">
        <tr>
            <td width="30%" class="fw-bold bg-light">Predikat Capaian Akhir 7KAIH</td>
            <td width="70%" class="fw-bold"><?= htmlspecialchars($kk['predikat_akhir'] ?? 'Sangat Baik') ?></td>
        </tr>
        <tr>
            <td class="fw-bold bg-light" style="vertical-align: top;">Catatan Guru / Wali Kelas</td>
            <td style="text-align: justify; min-height: 70px;">
                <?= nl2br(htmlspecialchars($kk['catatan_kokurikuler'] ?? 'Ananda menunjukkan komitmen positif dalam menerapkan 7 Kebiasaan Anak Indonesia Hebat di sekolah dan lingkungan keluarga.')) ?>
            </td>
        </tr>
    </table>

    <!-- Keterangan Skala Pembiasaan -->
    <div class="mb-4" style="font-size: 9pt; border: 1px dashed #666; padding: 6px 10px;">
        <strong>Keterangan Tingkat Pembiasaan 7KAIH:</strong>
        <strong>Membudaya</strong> (Dilakukan secara konsisten dan mandiri) &bull;
        <strong>Berkembang</strong> (Dilakukan secara rutin dengan kesadaran baik) &bull;
        <strong>Mulai Berkembang</strong> (Mulai dilakukan dengan pengingat/motivasi) &bull;
        <strong>Perlu Bimbingan</strong> (Memerlukan pendampingan intensif guru dan orang tua).
    </div>

    <!-- Kolom Pengesahan -->
    <table class="w-100 mt-3" style="font-size: 10.5pt; border: none;">
        <tr>
            <td width="35%" class="text-center" style="border: none;">
                Mengetahui,<br>Orang Tua / Wali
                <br><br><br><br>
                ( ........................................ )
            </td>
            <td width="30%" style="border: none;"></td>
            <td width="35%" class="text-center" style="border: none;">
                <?= htmlspecialchars($sekolah['tempat_cetak'] ?? 'Bandung') ?>, <?= htmlspecialchars($sekolah['tanggal_rapor'] ?? '20 Desember 2026') ?><br>
                Wali Kelas / Koordinator 7KAIH,
                <br><br><br><br>
                <u><strong><?= htmlspecialchars($siswa['wali_kelas'] ?? '........................................') ?></strong></u><br>
                NIP. <?= htmlspecialchars($siswa['nip_wali'] ?? '-') ?>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="text-center pt-2" style="border: none;">
                Mengetahui,<br>Kepala Sekolah
                <br><br><br><br>
                <u><strong><?= htmlspecialchars($sekolah['nama_kepsek'] ?? '........................................') ?></strong></u><br>
                NIP. <?= htmlspecialchars($sekolah['nip_kepsek'] ?? '-') ?>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
<?php
    exit;
}

include 'includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="fw-bold mb-0">Cetak Rapor Kokurikuler — 7 Kebiasaan Anak Indonesia Hebat (7KAIH)</h4>
        <small class="text-muted">Format laporan kokurikuler 7KAIH terpisah dari e-Rapor Intrakurikuler Kurikulum Merdeka.</small>
    </div>
    <a href="input_kokurikuler.php?kelas_id=<?= $kelas_id ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Input / Import Nilai 7KAIH
    </a>
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
    $q_s = mysqli_query($conn, "
        SELECT s.*, kk.predikat_akhir, kk.id as kk_id
        FROM siswa s
        LEFT JOIN nilai_kokurikuler_7kaih kk ON kk.siswa_id=s.id AND kk.semester='$semester' AND kk.tahun_ajaran='$ta'
        WHERE s.kelas_id=$kelas_id
        ORDER BY s.nama_siswa
    ");
?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Daftar Peserta Didik — Cetak Rapor Kokurikuler 7KAIH</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="18%">NIS / NISN</th>
                    <th width="32%">Nama Peserta Didik</th>
                    <th width="10%">L/P</th>
                    <th width="18%">Status Penilaian 7KAIH</th>
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
                        <?php if ($r['kk_id']): ?>
                            <span class="badge bg-success">Terisi (<?= htmlspecialchars($r['predikat_akhir']) ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Default / Belum Diisi</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?cetak_siswa=<?= $r['id'] ?>&semester=<?= $semester ?>&ta=<?= urlencode($ta) ?>" target="_blank" class="btn btn-sm btn-primary">
                            <i class="bi bi-printer-fill me-1"></i>Cetak Rapor 7KAIH
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
