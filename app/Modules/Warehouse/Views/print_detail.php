<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MR: <?= $mr->wr_matrequest_no ?></title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        .header { text-align: center; margin-bottom: 20px; }
        .info-table td { border: none; padding: 2px;}
        .sign-area { margin-top: 40px; width: 100%; page-break-inside: avoid; }
        .sign-col { width: 50%; float: left; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>MATERIAL REQUEST</h2>
        <h3><?= esc($mr->wr_matrequest_no) ?></h3>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%"><strong>No Kontrak:</strong> <?= esc($mr->mkt_contract_no) ?></td>
            <td width="50%"><strong>Tanggal MR:</strong> <?= esc($mr->wr_matrequest_date) ?></td>
        </tr>
         <tr>
            <td><strong>Jadwal Service:</strong> <?= esc($mr->opr_schedule_date) ?></td>
            <td><strong>Status:</strong> <?= esc($mr->wr_matrequest_status) ?></td>
        </tr>
    </table>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Lokasi</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
             <?php foreach($items as $index => $item): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= esc($item['inventory_kode']) ?></td>
                <td><?= esc($item['inventory_name']) ?></td>
                <td><?= esc($item['lokasi']) ?></td>
                <td><?= esc($item['wr_matrequest_item_item_qty']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="sign-area">
        <div class="sign-col">
            <p>Diserahkan oleh,</p>
            <img src="<?= $mr->wr_matrequest_sign1 ?>" height="80">
            <p>( <?= esc($mr->nama_pemberi) ?> )</p>
        </div>
        <div class="sign-col">
            <p>Diterima oleh,</p>
             <img src="<?= $mr->wr_matrequest_sign2 ?>" height="80">
            <p>( <?= esc($mr->nama_penerima) ?> )</p>
        </div>
    </div>
</body>
</html>