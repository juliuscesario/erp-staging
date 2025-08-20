<form id="payment-form" action="<?= site_url('finance/payment/store') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="invoice_uuid" value="<?= esc($invoice['inv_invoice_uuid']) ?>">
    <input type="hidden" id="grand_total" value="<?= esc($invoice['inv_invoice_grand_total']) ?>">
    <input type="hidden" id="subtotal" value="<?= esc($invoice['inv_invoice_subtotal']) ?>">
    <input type="hidden" id="discount" value="<?= esc($invoice['inv_invoice_discount'] ?? 0) ?>">
    <input type="hidden" id="ppn_total" value="<?= esc($invoice['inv_invoice_ppn_total']) ?>">
    <input type="hidden" name="payment_proof_base64" id="payment_proof_base64">
    
    <div class="mb-3">
        <label>No. Invoice</label>
        <input type="text" class="form-control" value="<?= esc($invoice['inv_invoice_no']) ?>" readonly>
    </div>

    <div class="mb-3">
        <label for="payment_type" class="form-label">Tipe Pembayaran</label>
        <select name="payment_type" id="payment_type" class="form-select" required>
            <option value="Bayar Penuh">Bayar Penuh</option>
            <option value="Bayar dengan Potongan PPh 23">Bayar dengan Potongan PPh 23 (2%)</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Jumlah Dibayar</label>
        <input type="text" class="form-control" id="payment_amount" name="payment_amount" value="<?= esc($invoice['inv_invoice_grand_total']) ?>" readonly>
    </div>
    
    <div class="mb-3">
        <label for="payment_date" class="form-label">Tanggal Bayar</label>
        <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="mb-3">
        <label for="payment_method" class="form-label">Metode Pembayaran</label>
        <select name="payment_method" id="payment_method" class="form-select" required>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="payment_proof_file" class="form-label">Bukti Pembayaran</label>
        <input class="form-control" type="file" id="payment_proof_file" name="payment_proof_file" accept="image/*,.pdf">
    </div>
    <div class="mb-3">
        <label for="payment_reference" class="form-label">Catatan/Referensi</label>
        <textarea name="payment_reference" id="payment_reference" class="form-control"></textarea>
    </div>
    
    <button type="submit" class="btn btn-primary mt-3" id="submit-payment">Simpan Pembayaran</button>
</form>

<script>
    $(document).ready(function() {
        // Event listener untuk input file
        $('#payment_proof_file').on('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Simpan hasil base64 ke input hidden
                    $('#payment_proof_base64').val(e.target.result);
                };
                reader.readAsDataURL(file);
            } else {
                $('#payment_proof_base64').val('');
            }
        });

        $('#payment_type').on('change', function() {
            let paymentType = $(this).val();
            let grandTotal = parseFloat($('#grand_total').val()) || 0;
            let subtotal = parseFloat($('#subtotal').val()) || 0;
            let discount = parseFloat($('#discount').val()) || 0;
            let ppnTotal = parseFloat($('#ppn_total').val()) || 0;
            let amountField = $('#payment_amount');

            if (paymentType === 'Bayar dengan Potongan PPh 23') {
                let dpp = subtotal - discount;
                let pphAmount = dpp * 0.02;
                let newAmount = dpp - pphAmount + ppnTotal;
                amountField.val(newAmount.toFixed(2));
            } else {
                amountField.val(grandTotal.toFixed(2));
            }
        });

        $('#payment-form').on('submit', function() {
            $('#submit-payment').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...');
        });
    });
</script>