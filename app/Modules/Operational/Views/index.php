<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Create Job Service
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Create Job Service</h3>
        <div>
            <a href="<?= site_url('operational/opr_service') ?>" class="btn btn-secondary">&laquo; Kembali ke Daftar</a>
        </div>
    </div>
    <!-- tampilan kalo ada invoice overdue -->
    <?php if ($on_hold): ?>
        
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Pembuatan Job Service Ditangguhkan!</h4>
            <p>Sistem mendeteksi ada invoice yang telah melewati tanggal jatuh tempo dan belum dilunasi. Harap selesaikan pembayaran untuk invoice berikut sebelum dapat membuat Job Service baru.</p>
            <hr>
            <ul>
                <?php foreach ($overdue_invoices as $invoice): ?>
                    <li>
                        <strong><?= esc($invoice['inv_invoice_no']) ?></strong> (Jatuh Tempo: <?= date('d M Y', strtotime($invoice['inv_invoice_due_date'])) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    <?php else: ?>

    <!-- tampilan kalo semua aman -->
    <form id="job-service-form" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="schedule-selector" class="form-label">Pilih Jadwal Servis</label>
            <select id="schedule-selector" name="schedule_uuid" class="form-select">
                <option value="">-- Pilih Jadwal --</option>
                <?php if (!empty($schedules)): ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <option value="<?= esc($schedule['opr_schedule_uuid']) ?>">
                            Jadwal #<?= esc($schedule['opr_schedule_id']) ?> - <?= esc($schedule['opr_schedule_date']) ?> (<?= esc($schedule['cust_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div id="details-section" class="mt-4" style="display:none;">
            <div class="card mb-4">
                <div class="card-header">Detail Informasi</div>
                <div class="card-body row">
                    <div class="col-md-6">
                        <p><strong>Nama Perusahaan:</strong> <span id="cust-name"></span></p>
                        <p><strong>Alamat:</strong> <span id="cust-address"></span></p>
                        <p><strong>No Kontrak:</strong> <span id="contract-no"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Hari:</strong> <span id="service-day"></span></p>
                        <p><strong>Periode Servis:</strong> <span id="service-period-full"></span></p>
                    </div>
                </div>
            </div>

            <h4 class="mt-4">Checklist Mesin</h4>
            <table id="checklist-table" class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Lokasi/Ruangan</th>
                        <th>Mesin</th>
                        <th>Problem/Kondisi</th>
                        <th>Tindakan</th>
                        <th>Lama Kerja</th>
                        <th>Foto</th>
                    </tr>
                </thead>
                <tbody id="checklist-body"></tbody>
            </table>

            <h4 class="mt-4">Tanda Tangan Pelanggan</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="pic-selector" class="form-label">Nama PIC</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPicModal">
                            + PIC Baru
                        </button>
                    </div>
                    <select name="cust_pic_uuid" id="pic-selector" class="form-select mb-2"></select>
                    <label class="form-label">Jabatan</label>
                    <p id="pic-position-display" class="form-control-plaintext">-</p>
                    <input type="hidden" name="cust_sign_name" id="pic-name-hidden">
                    <input type="hidden" name="cust_sign_position" id="pic-position-hidden">
                    
                    <canvas id="signature-pad" class="signature-canvas" width="400" height="200"></canvas>
                    <button type="button" class="btn btn-secondary btn-sm mt-2" id="clear-signature">Clear</button>
                    <input type="hidden" name="cust_sign_image" id="signature-data">
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Simpan Job Service</button>
        </div>
    </form>

    <div class="modal fade" id="addPicModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah PIC Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="new-pic-form-container"></div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        window.siteUrl = "<?= site_url('/') ?>"; 
    </script>
    <?php include('Js/index_js.php'); ?>
<?= $this->endSection() ?>