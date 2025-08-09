<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Service: <?= esc($service['opr_service_no']) ?></title>
    <style>
        @page { margin: 25px; }
        body { font-family: sans-serif; font-size: 11px; }
        h3, h4 { text-align: center; margin-bottom: 15px; }
        /* Tabel utama untuk data dan item */
        .main-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .main-table th, .main-table td { border: 1px solid #aaa; padding: 6px; text-align: left; vertical-align: top; }
        .main-table th { background-color: #f2f2f2; }

        /* Tabel khusus untuk layout informasi, tanpa border */
        .layout-table { width: 100%; border: none; margin-bottom: 20px; }
        .layout-table td { border: none; padding: 0; vertical-align: top; width: 50%; }

        .page-break { page-break-after: always; }
        .photo-appendix { margin-top: 20px; text-align: center; }
        .photo-appendix img { max-width: 80%; border: 1px solid #ddd; margin-top: 10px; }
    </style>
</head>
<body>
    <h3>Laporan Job Service: <?= esc($service['opr_service_no']) ?></h3>
    
    <table class="layout-table">
        <tr>
            <td>
                <p><strong>Nama Perusahaan:</strong><br><?= esc($service['cust_name']) ?></p>
                <p><strong>Alamat:</strong><br>
                    <?php 
                        $alamat_lengkap = [
                            $service['building_add1'], $service['building_kelurahan'], $service['building_kecamatan'], 
                            $service['building_kabupaten'], $service['building_kode_pos']
                        ];
                        echo esc(implode(', ', array_filter($alamat_lengkap)));
                    ?>
                </p>
                <p><strong>No Kontrak:</strong><br><?= esc($service['mkt_contract_no']) ?></p>
            </td>
            <td>
                <p><strong>Hari:</strong><br><?= esc($service['service_day']) ?></p>
                <p><strong>Periode Servis:</strong><br><?= esc($service['service_period_full']) ?></p>
                <p><strong>Periode:</strong><br><?= esc($service['opr_schedule_period_run']) ?> dari <?= esc($service['opr_schedule_period_total']) ?></p>
            </td>
        </tr>
    </table>

    <h4>Checklist Pengerjaan</h4>
    <table class="main-table">
        <thead>
            <tr>
                <th>Lokasi</th>
                <th>Mesin</th>
                <th>Problem</th>
                <th>Tindakan</th>
                <th>Lama Kerja</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?= esc($item['building_name'] ?? '') ?> - <?= esc($item['room_name'] ?? '') ?></td>
                <td><?= esc($item['inventory_name']) ?></td>
                <td><?= esc($item['opr_service_item_problem']) ?></td>
                <td><?= esc($item['opr_service_item_action']) ?></td>
                <td><?= esc($item['opr_service_item_operation']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h4 style="margin-top: 30px;">Tanda Tangan Pelanggan</h4>
    <table class="main-table">
        <thead>
            <tr>
                <th>
                    PIC: <?= esc($service['cust_pic_name'] ?? 'N/A') ?> 
                    (<?= esc($service['opr_service_cust_sign_position'] ?? 'N/A') ?>)
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center; padding: 10px;">
                    <?php if(!empty($service['opr_service_cust_pic_sign'])): ?>
                        <img src="<?= $service['opr_service_cust_pic_sign'] ?>" alt="Tanda Tangan" style="max-height: 100px; width: auto;">
                    <?php else: ?>
                        (Tidak ada tanda tangan)
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
    
    <div class="page-break"></div>

    <h3>Lampiran Foto Pengerjaan</h3>
    
    <?php foreach($items as $item): ?>
        <?php if(!empty($item['opr_service_item_image'])): ?>
            <div class="photo-appendix">
                <h4><?= esc($item['inventory_name']) ?> di <?= esc($item['room_name'] ?? 'Lokasi tidak spesifik') ?></h4>
                <p><strong>Problem:</strong> <?= esc($item['opr_service_item_problem']) ?></p>
                <p><strong>Tindakan:</strong> <?= esc($item['opr_service_item_action']) ?></p>
                <img src="<?= $item['opr_service_item_image'] ?>">
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

</body>
</html>