<!DOCTYPE html>
<html>
<head>
    <title>Invoice: <?= esc($invoice['inv_invoice_no']) ?></title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: right; }
        .header h1 { margin: 0; color: #007bff; }
        .details-table { width: 100%; margin-top: 30px; }
        .details-table td { vertical-align: top; padding: 5px; }
        .bill-to { width: 60%; }
        .invoice-info { width: 40%; }
        .items-table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; }
        .items-table th { background-color: #f2f2f2; text-align: left; }
        .totals-table { width: 40%; float: right; margin-top: 20px; }
        .totals-table td { padding: 5px; }
        .terbilang { clear: both; margin-top: 30px; font-style: italic; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
    </div>

    <table class="details-table">
        <tr>
            <td class="bill-to">
                <strong>Bill To:</strong><br>
                <?= esc($invoice['cust_name']) ?><br>
                <?= nl2br(esc($invoice['bill_tax_alamat'])) ?><br>
                NPWP: <?= esc($invoice['bill_tax_npwp']) ?>
            </td>
            <td class="invoice-info">
                <strong>No. Invoice:</strong> <?= esc($invoice['inv_invoice_no']) ?><br>
                <strong>Tanggal:</strong> <?= date('d M Y', strtotime($invoice['inv_invoice_date'])) ?><br>
                <strong>No. Kontrak:</strong> <?= esc($invoice['mkt_contract_no']) ?><br>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:70%;">Deskripsi</th>
                <th>Tanggal Servis</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($invoice_details)): ?>
                <?php foreach($invoice_details as $detail): ?>
                    
                    <tr>
                        <td>
                            <?php 
                                $deskripsi_sr = ($detail['period'] == 1) ? 'Jasa Instalasi Awal' : 'Jasa Perawatan Rutin';
                            ?>
                            <strong><?= esc($deskripsi_sr) ?> - <?= esc($detail['service_no']) ?></strong>
                            <br><small>(Ref. MR: <?= esc($detail['mr_no']) ?>)</small>
                        </td>
                        <td><?= date('d M Y', strtotime($detail['service_date'])) ?></td>
                    </tr>

                    <?php if(!empty($detail['items'])): ?>
                        <?php foreach($detail['items'] as $item): ?>
                            <?php
                                // Determine the item description based on its type
                                $deskripsi_item = esc($item['inventory_name']);
                                if ($item['inventory_jenis'] === 'Asset') {
                                    $lokasi = ' (Lokasi: ' . esc($item['building_name'] ?? 'N/A') . ' - ' . esc($item['room_name'] ?? 'N/A') . ')';
                                    $deskripsi_item = 'Sewa ' . $deskripsi_item . $lokasi;
                                } else { // For 'Retail' items
                                    $deskripsi_item = 'Refill ' . $deskripsi_item;
                                }
                            ?>
                            <tr style="background-color: #fafafa;">
                                <td style="padding-left: 25px;">
                                    <em>- <?= $deskripsi_item ?></em>
                                </td>
                                <td>
                                    <em>Qty: <?= esc($item['wr_matrequest_item_item_qty']) ?></em>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2" class="text-center">Tidak ada rincian pekerjaan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <table class="totals-table">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td style="text-align:right;"><?= number_format($invoice['inv_invoice_subtotal'], 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>Diskon:</strong></td>
            <td style="text-align:right;"><?= number_format($invoice['inv_invoice_discount'], 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>PPN:</strong></td>
            <td style="text-align:right;"><?= number_format($invoice['inv_invoice_ppn_total'], 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>GRAND TOTAL:</strong></td>
            <td style="text-align:right;"><strong><?= number_format($invoice['inv_invoice_grand_total'], 2, ',', '.') ?></strong></td>
        </tr>
    </table>

    <div class="terbilang">
        <strong>Terbilang:</strong> <?= esc($terbilang) ?>
    </div>

    <div class="footer">
        <strong>Payment to:</strong><br>
        Bank: <?= esc($bank['bank_name']) ?> | 
        A/C No: <?= esc($bank['bank_account_number']) ?> | 
        A/N: <?= esc($bank['bank_account_name']) ?>
    </div>

</body>
</html>