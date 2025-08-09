<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Material Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Detail Material Request: <?= esc($mr->wr_matrequest_no) ?></h3>
        <a href="<?= site_url('warehouse/materialrequest/printPdf/' . $mr->wr_matrequest_uuid) ?>" target="_blank" class="btn btn-success">
            Cetak PDF
        </a>
        <a href="<?= site_url('warehouse/materialrequest') ?>" class="btn btn-secondary">
            &laquo; Kembali ke Daftar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>No. Kontrak:</strong><br><?= esc($mr->mkt_contract_no) ?></p>
                    <p><strong>Status:</strong><br><span class="badge bg-warning"><?= esc($mr->wr_matrequest_status) ?></span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Tanggal MR:</strong><br><?= esc($mr->wr_matrequest_date) ?></p>
                    <p><strong>Tanggal Jadwal Servis:</strong><br><?= esc($mr->opr_schedule_date) ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-4">Daftar Item</h4>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Qty</th>
                        <th>QR Code</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $index => $item): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= esc($item->inventory_kode) ?></td>
                        <td><?= esc($item->inventory_name) ?></td>
                        <td><?= esc($item->wr_matrequest_item_item_qty) ?></td>
                        <td>
                            <?php if(!empty($item->wr_matrequest_item_qrcode_image)): ?>
                                <img src="<?= esc($item->wr_matrequest_item_qrcode_image) ?>" alt="QR Code">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                         <td>
                            <?php if(!empty($item->wr_matrequest_item_qrcode_image)): ?>
                                <a href="<?= site_url('warehouse/materialrequest/printSticker/' . $mr->wr_matrequest_uuid . '/' . $item->wr_matrequest_item_id) ?>" target="_blank" class="btn btn-sm btn-info">
                                    Cetak Stiker
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h4 class="mt-4">Tanda Tangan</h4>
    <div class="row">
        <div class="col-md-6 text-center">
            <div class="card">
                <div class="card-header">Diserahkan oleh: <strong><?= esc($mr->nama_pemberi) ?></strong></div>
                <div class="card-body">
                    <img src="<?= esc($mr->wr_matrequest_sign1) ?>" alt="Tanda Tangan 1" style="max-width: 100%; height: auto;">
                </div>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div class="card">
                <div class="card-header">Diterima oleh: <strong><?= esc($mr->nama_penerima) ?></strong></div>
                <div class="card-body">
                    <img src="<?= esc($mr->wr_matrequest_sign2) ?>" alt="Tanda Tangan 2" style="max-width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>