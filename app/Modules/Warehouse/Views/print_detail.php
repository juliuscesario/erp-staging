<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MR: <?= esc($mr->wr_matrequest_no) ?></title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .container { width: 100%; margin: 0 auto; }
        h3, h4 { text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: left; vertical-align: middle; }
        th { background-color: #f2f2f2; }
        .row { display: table; width: 100%; margin-top: 30px; }
        .col { display: table-cell; width: 50%; text-align: center; }
        .signature-box { border: 1px solid #999; padding: 10px; margin: 0 10px; }
        .signature-box img { max-width: 180px; max-height: 90px; }
        /* Style untuk gambar QR Code */
        .qr-code-img { width: 80px; height: 80px; }
    </style>
</head>
<body>
    <div class="container">
        <h3>Detail Material Request: <?= esc($mr->wr_matrequest_no) ?></h3>
        
        <table>
            <tr>
                <td><strong>No. Kontrak:</strong></td>
                <td><?= esc($mr->mkt_contract_no) ?></td>
                <td><strong>Tanggal MR:</strong></td>
                <td><?= esc($mr->wr_matrequest_date) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td><?= esc($mr->wr_matrequest_status) ?></td>
                <td><strong>Tanggal Jadwal:</strong></td>
                <td><?= esc($mr->opr_schedule_date) ?></td>
            </tr>
        </table>

        <h4>Daftar Item</h4>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 15%;">QR Code</th> </tr>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $index => $item): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= esc($item->inventory_kode) ?></td>
                    <td><?= esc($item->inventory_name) ?></td>
                    <td style="text-align: center;"><?= esc($item->wr_matrequest_item_item_qty) ?></td>
                    <td style="text-align: center;">
                        <?php if(!empty($item->wr_matrequest_item_qrcode_image)): ?>
                            <img src="<?= $item->wr_matrequest_item_qrcode_image ?>" alt="QR Code" class="qr-code-img">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row">
            <div class="col">
                <div class="signature-box">
                    <p>Diserahkan oleh:<br><strong><?= esc($mr->nama_pemberi) ?></strong></p>
                    <img src="<?= esc($mr->wr_matrequest_sign1) ?>" alt="Tanda Tangan 1">
                </div>
            </div>
            <div class="col">
                <div class="signature-box">
                    <p>Diterima oleh:<br><strong><?= esc($mr->nama_penerima) ?></strong></p>
                    <img src="<?= esc($mr->wr_matrequest_sign2) ?>" alt="Tanda Tangan 2">
                </div>
            </div>
        </div>
    </div>
</body>
</html>