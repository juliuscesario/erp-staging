<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Edit Job Service: <?= esc($service['opr_service_no']) ?>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Edit Job Service: <?= esc($service['opr_service_no']) ?></h3>
        <div>
            <a href="<?= site_url('operational/opr_service') ?>" class="btn btn-secondary">&laquo; Kembali ke Daftar</a>
        </div>
    </div>
    <form id="job-service-form" action="<?= site_url('operational/opr_service/update/' . $service['opr_service_uuid']) ?>" method="post" enctype="multipart/form-data">
        
        <div class="card mb-4">
            <div class="card-header">Detail Informasi</div>
            <div class="card-body row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>Nama Perusahaan:</strong><br><?= esc($service['cust_name'] ?? '-') ?></p>
                    <p class="mb-2"><strong>Alamat:</strong><br>
                        <?php 
                            $alamat_lengkap = [
                                $service['building_add1'] ?? null, $service['building_kelurahan'] ?? null, $service['building_kecamatan'] ?? null, 
                                $service['building_kabupaten'] ?? null, $service['building_kode_pos'] ?? null
                            ];
                            echo esc(implode(', ', array_filter($alamat_lengkap)));
                        ?>
                    </p>
                    <p class="mb-2"><strong>No Kontrak:</strong><br><?= esc($service['mkt_contract_no'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2"><strong>Hari:</strong><br><?= esc($service['service_day'] ?? '-') ?></p>
                    <p class="mb-2"><strong>Periode Servis:</strong><br><?= esc($service['service_period_full'] ?? '-') ?></p>
                </div>
            </div>
        </div>

        <h4 class="mt-4">Checklist Pengerjaan</h4>
        <table id="checklist-table" class="table table-bordered">
            <thead>
                <tr><th>Lokasi/Ruangan</th><th>Mesin</th><th>Problem/Kondisi</th><th>Tindakan</th><th>Lama Kerja</th><th>Foto</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach($items as $item): ?>
                        <tr>
                            <td><?= esc($item['building_name'] ?? '') ?> - <?= esc($item['room_name'] ?? '') ?></td>
                            <td><?= esc($item['inventory_name'] ?? 'Item Tidak Ditemukan') ?></td>
                            <td>
                                <input type="text" name="items[<?= $item['opr_service_item_uuid'] ?>][problem]" class="form-control" value="<?= esc($item['opr_service_item_problem']) ?>">
                            </td>
                            <td>
                                <input type="text" name="items[<?= $item['opr_service_item_uuid'] ?>][action]" class="form-control" value="<?= esc($item['opr_service_item_action']) ?>">
                            </td>
                            <td>
                                <input type="text" name="items[<?= $item['opr_service_item_uuid'] ?>][work_duration]" class="form-control" value="<?= esc($item['opr_service_item_operation']) ?>">
                            </td>
                            <td>
                                <?php if(!empty($item['opr_service_item_image'])): ?>
                                    <div class="mb-2">
                                        <a href="<?= $item['opr_service_item_image'] ?>" data-fancybox="gallery-edit" data-caption="Foto tersimpan untuk <?= esc($item['inventory_name']) ?>">
                                            <img src="<?= $item['opr_service_item_image'] ?>" alt="Foto Tersimpan" style="width: 100px; height: auto;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <input type="file" class="form-control photo-input" data-uuid="<?= $item['opr_service_item_uuid'] ?>">
                                <input type="hidden" name="items[<?= $item['opr_service_item_uuid'] ?>][photo_base64]" class="photo-base64-input">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
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
                <select name="cust_pic_uuid" id="pic-selector" class="form-select mb-2">
                    <option value="">Pilih PIC</option>
                    <?php if(!empty($pics)): ?>
                        <?php foreach($pics as $pic): ?>
                            <option value="<?= $pic['cust_pic_uuid'] ?>" <?= ($pic['cust_pic_uuid'] == $service['opr_service_cust_pic_uuid']) ? 'selected' : '' ?>>
                                <?= esc($pic['cust_pic_panggilan'] . ' ' . $pic['cust_pic_kontak']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                
                <label class="form-label">Jabatan</label>
                <p id="pic-position-display" class="form-control-plaintext"><?= esc($service['opr_service_cust_sign_position'] ?? '-') ?></p>
                <input type="hidden" name="cust_sign_position" id="pic-position-hidden" value="<?= esc($service['opr_service_cust_sign_position'] ?? '') ?>">
                <input type="hidden" name="cust_sign_name" id="pic-name-hidden">
                
                <div class="mt-2">
                    <p>Tanda Tangan Tersimpan:</p>
                    <?php if(!empty($service['opr_service_cust_pic_sign'])): ?>
                        <img id="saved-signature" src="<?= $service['opr_service_cust_pic_sign'] ?>" alt="Tanda Tangan Tersimpan" style="border: 1px solid #ccc; max-width: 200px;">
                        <p><small>Gambar ulang di bawah untuk mengganti.</small></p>
                    <?php else: ?>
                        <p><small>Belum ada tanda tangan.</small></p>
                    <?php endif; ?>
                </div>

                <canvas id="signature-pad" class="signature-canvas mt-2" width="400" height="200"></canvas>
                <button type="button" class="btn btn-secondary btn-sm mt-2" id="clear-signature">Clear</button>
                <input type="hidden" name="cust_sign_image" id="signature-data">
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-4">Update Job Service</button>
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
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        // Mendefinisikan variabel global untuk digunakan oleh edit_js.php
        window.siteUrl = "<?= site_url('/') ?>";
        window.serviceData = <?= json_encode($service ?? null) ?>;
        window.picsData = <?= json_encode($pics ?? []) ?>;
    </script>
    <?php include('Js/edit_js.php'); ?>
<?= $this->endSection() ?>