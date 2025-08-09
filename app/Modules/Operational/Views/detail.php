<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Detail Job Service: <?= esc($service['opr_service_no'] ?? 'N/A') ?>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Detail Job Service: <?= esc($service['opr_service_no'] ?? 'N/A') ?></h3>
        <div>
            <a href="<?= site_url('operational/opr_service/printService/' . ($service['opr_service_uuid'] ?? '')) ?>" target="_blank" class="btn btn-success">Cetak Laporan</a>
            <a href="<?= site_url('operational/opr_service') ?>" class="btn btn-secondary">&laquo; Kembali ke Daftar</a>
        </div>
    </div>

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
            <tr>
                <th>Lokasi</th>
                <th>Mesin</th>
                <th>Problem</th>
                <th>Tindakan</th>
                <th>Lama Kerja</th>
                <th>Foto</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><?= esc($item['building_name']?? '') ?> - <?= esc($item['room_name'] ?? '') ?></td>
                    <td><?= esc($item['inventory_name'] ?? 'N/A') ?></td>
                    <td><?= esc($item['opr_service_item_problem'] ?? '-') ?></td>
                    <td><?= esc($item['opr_service_item_action'] ?? '-') ?></td>
                    <td><?= esc($item['opr_service_item_operation'] ?? '-') ?></td>
                    <td>
                        <?php if(!empty($item['opr_service_item_image'])): ?>
                            <a href="<?= $item['opr_service_item_image'] ?>" data-fancybox="gallery" data-caption="Foto untuk <?= esc($item['inventory_name']) ?>">
                                <img src="<?= $item['opr_service_item_image'] ?>" alt="Foto Pengerjaan" style="width: 100px; height: auto;">
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">Tidak ada item pekerjaan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h4 class="mt-4">Tanda Tangan Pelanggan</h4>
    <div class="card" style="width: 30rem;">
        <div class="card-header">
            PIC: <strong><?= esc($service['cust_pic_name'] ?? '-') ?></strong>
            (<?= esc($service['opr_service_cust_sign_position'] ?? '-') ?>)
        </div>
        <div class="card-body text-center">
             <?php if(!empty($service['opr_service_cust_pic_sign'])): ?>
                <img src="<?= esc($service['opr_service_cust_pic_sign']) ?>" alt="Tanda Tangan Pelanggan" style="max-width: 100%; height: auto;">
            <?php else: ?>
                <p>(Belum ada tanda tangan)</p>
            <?php endif; ?>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <?php include('Js/detail_js.php'); ?>
<?= $this->endSection() ?>