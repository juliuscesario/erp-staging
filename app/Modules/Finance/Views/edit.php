<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Edit Invoice: <?= esc($invoice['inv_invoice_no']) ?>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <h3>Edit Invoice: <?= esc($invoice['inv_invoice_no']) ?></h3>
    <hr>

    <form action="<?= site_url('finance/invoice/update/' . $invoice['inv_invoice_uuid']) ?>" method="post">
        <?= csrf_field() ?>
        
        <div class="card">
            <div class="card-header">Detail Informasi</div>
            <div class="card-body row">
                <div class="col-md-6">
                    <p><strong>Ditagihkan Kepada:</strong><br><?= esc($invoice['cust_name']) ?></p>
                    <p><strong>No. Kontrak:</strong><br><?= esc($invoice['mkt_contract_no']) ?></p>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="inv_invoice_date" class="form-label"><strong>Tanggal Invoice:</strong></label>
                        <input type="date" class="form-control" id="inv_invoice_date" name="inv_invoice_date" value="<?= esc($invoice['inv_invoice_date']) ?>">
                    </div>
                </div>
            </div>
        </div>

        <h4 class="mt-4">Rincian Tagihan</h4>
        <table class="table table-bordered">
            <thead><tr><th>Deskripsi</th><th>Tanggal Pengerjaan</th></tr></thead>
            <tbody>
            <?php if (!empty($invoice_details)): foreach($invoice_details as $detail): ?>
                <tr><td><strong><?= ($detail['period'] == 1) ? 'Jasa Instalasi Awal' : 'Jasa Perawatan Rutin' ?> - <?= esc($detail['service_no']) ?></strong><br><small>(Ref. MR: <?= esc($detail['mr_no']) ?>)</small></td><td><?= date('d M Y', strtotime($detail['service_date'])) ?></td></tr>
                <?php if(!empty($detail['items'])): foreach($detail['items'] as $item): ?>
                    <tr style="background-color: #fafafa;"><td style="padding-left: 25px;"><em>- <?= esc($item['inventory_jenis'] === 'Asset' ? 'Sewa ' . $item['inventory_name'] . ' (Lokasi: ' . esc($item['building_name'] ?? 'N/A') . ' - ' . esc($item['room_name'] ?? 'N/A') . ')' : 'Refill ' . $item['inventory_name']) ?></em></td><td><em>Qty: <?= esc($item['wr_matrequest_item_item_qty']) ?></em></td></tr>
                <?php endforeach; endif; ?>
            <?php endforeach; else: ?>
                <tr><td colspan="2" class="text-center">Tidak ada rincian pekerjaan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="row mt-3">
            <div class="col-md-6 offset-md-6">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td class="text-end" id="subtotal_display"><?= number_format($invoice['inv_invoice_subtotal'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Diskon:</strong></td>
                        <td class="text-end">
                            <input type="number" class="form-control text-end" id="inv_invoice_discount" name="inv_invoice_discount" value="<?= esc($invoice['inv_invoice_discount']) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="apply_tax" id="apply_tax" <?= ($invoice['inv_invoice_ppn_total'] > 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="apply_tax">
                                    <strong>PPN (11%):</strong>
                                </label>
                            </div>
                        </td>
                        <td class="text-end" id="ppn_display"><?= number_format($invoice['inv_invoice_ppn_total'], 0, ',', '.') ?></td>
                    </tr>
                    <tr class="table-primary">
                        <td><h4><strong>Grand Total:</strong></h4></td>
                        <td class="text-end"><h4 id="grand_total_display"><?= number_format($invoice['inv_invoice_grand_total'], 0, ',', '.') ?></h4></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="<?= site_url('finance/invoice/detail/' . $invoice['inv_invoice_uuid']) ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    $(document).ready(function() {
        const subtotal = <?= (float)$invoice['inv_invoice_subtotal'] ?>;
        const discountInput = $('#inv_invoice_discount');
        const taxCheckbox = $('#apply_tax');
        
        function updateTotals() {
            let discount = parseFloat(discountInput.val()) || 0;
            let baseForTax = subtotal - discount;
            let ppn = 0;

            if (taxCheckbox.is(':checked')) {
                ppn = baseForTax * 0.11;
            }
            
            let grandTotal = baseForTax + ppn;

            const formatter = new Intl.NumberFormat('id-ID');
            $('#ppn_display').text(formatter.format(ppn.toFixed(0)));
            $('#grand_total_display').text(formatter.format(grandTotal.toFixed(0)));
        }

        // Event listeners
        discountInput.on('input', updateTotals);
        taxCheckbox.on('change', updateTotals);

        // Initial call to set values on page load
        updateTotals();
    });
</script>
<?= $this->endSection() ?>