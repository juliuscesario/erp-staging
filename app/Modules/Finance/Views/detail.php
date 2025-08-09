<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Detail Invoice: <?= esc($invoice['inv_invoice_no']) ?>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Detail Invoice: <?= esc($invoice['inv_invoice_no']) ?></h3>
        <div>
            <a href="<?= site_url('finance/invoice/printPdf/' . $invoice['inv_invoice_uuid']) ?>" target="_blank" class="btn btn-success">
                Cetak PDF
            </a>
            <a href="<?= site_url('finance/invoice/list') ?>" class="btn btn-secondary">&laquo; Kembali ke Daftar</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Detail Informasi</div>
        <div class="card-body row">
            <div class="col-md-6">
                <p><strong>Ditagihkan Kepada:</strong><br><?= esc($invoice['cust_name']) ?></p>
                <p><strong>Alamat Penagihan:</strong><br>
                    <?php 
                        $alamat_lengkap = [
                            $invoice['building_add1'] ?? null, 
                            $invoice['building_kelurahan'] ?? null, 
                            $invoice['building_kecamatan'] ?? null, 
                            $invoice['building_kabupaten'] ?? null, 
                            $invoice['building_kode_pos'] ?? null
                        ];
                        // Tampilkan alamat jika ada isinya, jika tidak tampilkan pesan alternatif
                        $alamat_final = implode(', ', array_filter($alamat_lengkap));
                        echo esc($alamat_final ?: 'Alamat tidak tersedia');
                    ?>
                </p>
                <p><strong>NPWP:</strong><br><?= esc($invoice['bill_tax_npwp'] ?? '-') ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>No. Kontrak:</strong><br><?= esc($invoice['mkt_contract_no']) ?></p>
                <p><strong>Tanggal Invoice:</strong><br><?= date('d M Y', strtotime($invoice['inv_invoice_date'])) ?></p>
                <p><strong>Status:</strong><br>
                    <?php $badge_class = ($invoice['inv_invoice_status'] === 'Paid') ? 'bg-success' : 'bg-warning text-dark'; ?>
                    <span class="badge <?= $badge_class ?>"><?= esc($invoice['inv_invoice_status']) ?></span>
                </p>
            </div>
        </div>
    </div>

    <h4 class="mt-4">Rincian Tagihan</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th>Tanggal Pengerjaan</th>
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

    <div class="row mt-3">
        <div class="col-md-6 offset-md-6 text-end">
            <p><strong>Subtotal:</strong> <?= number_format($invoice['inv_invoice_subtotal'], 0, ',', '.') ?></p>
            <p><strong>Diskon:</strong> <?= number_format($invoice['inv_invoice_discount'], 0, ',', '.') ?></p>
            <p><strong>PPN:</strong> <?= number_format($invoice['inv_invoice_ppn_total'], 0, ',', '.') ?></p>
            <hr>
            <h4><strong>Grand Total:</strong> <?= number_format($invoice['inv_invoice_grand_total'], 0, ',', '.') ?></h4>
        </div>
    </div>
<?= $this->endSection() ?>