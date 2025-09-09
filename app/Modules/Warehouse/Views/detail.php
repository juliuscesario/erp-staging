<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Material Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h4 class="mt-4">Daftar Item</h4>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Lokasi</th>
                        <th>Qty</th>
                        <th>QR Code</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach($items as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= esc($item['inventory_kode']) ?></td>
                            <td><?= esc($item['inventory_name']) ?></td>
                            <td><?= esc($item['building_name']) ?><?= !empty($item['room_name']) ? ' / ' . esc($item['room_name']) : '' ?></td>
                            <td><?= esc($item['wr_matrequest_item_item_qty']) ?></td>
                            <td>
                                <?php if(!empty($item['qrcode_image'])): ?>
                                    <img src="<?= $item['qrcode_image'] ?>" alt="QR Code" width="100">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                             <td>
                                <?php
                                $decoded_data = json_decode($item['wr_matrequest_item_batch_uuid'], true);
                                if(json_last_error() === JSON_ERROR_NONE && isset($decoded_data['qr_link'])): ?>
                                    <a href="<?= site_url('warehouse/materialrequest/printSticker/' . $mr['wr_matrequest_uuid'] . '/' . $item['wr_matrequest_item_id']) ?>" target="_blank" class="btn btn-sm btn-info">
                                        Cetak Stiker
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Tidak ada item.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h4 class="mt-4">Tanda Tangan</h4>
    <div class="row">
        <div class="col-md-6 text-center">
            <div class="card">
                <div class="card-header">Diserahkan oleh: <strong><?= esc($mr['nama_pemberi']) ?></strong></div>
                <div class="card-body"><img src="<?= esc($mr['wr_matrequest_sign1']) ?>" alt="Tanda Tangan 1" style="max-width: 100%;"></div>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div class="card">
                <div class="card-header">Diterima oleh: <strong><?= esc($mr['nama_penerima']) ?></strong></div>
                <div class="card-body"><img src="<?= esc($mr['wr_matrequest_sign2']) ?>" alt="Tanda Tangan 2" style="max-width: 100%;"></div>
            </div>
        </div>
    </div>
</body>
</html>