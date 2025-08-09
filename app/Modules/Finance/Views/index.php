<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Buat Invoice Baru<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Buat Invoice</h1>
        <a href="<?= site_url('finance/invoice/list') ?>" class="btn btn-primary">
            List Invoice
        </a>
    </div>
    <form id="invoice-form">
        <div class="mb-3">
            <label for="contract-selector" class="form-label">Pilih Kontrak</label>
            <select id="contract-selector" name="contract_uuid" class="form-select">
                <option value="">-- Pilih Kontrak --</option>
                <?php foreach($contracts as $contract): ?>
                    <option value="<?= $contract['mkt_contract_uuid'] ?>"><?= $contract['mkt_contract_no'] ?> - <?= $contract['cust_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="details-section" style="display:none;" class="mt-4">
            <h4 id="invoice-number-display" class="mb-3 text-primary"></h4>
            <div class="mb-3 row">
                <label for="invoice_date" class="col-sm-2 col-form-label">Tanggal Invoice</label>
                <div class="col-sm-4">
                    <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <hr>

            <div class="card mb-4">
                <div class="card-header">Detail Informasi Kuotasi</div>
                <div id="quotation-details" class="card-body row"></div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">Daftar Item Sesuai Kuotasi</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Nama Item (Lokasi)</th><th>Qty</th><th>Harga</th><th>Total</th></tr></thead>
                        <tbody id="quotation-items-table"></tbody>
                    </table>
                </div>
            </div>

            <div id="service-checklist" class="mb-3 card card-body">
                <h5>Pilih Paket Invoice yang Akan Dibuat</h5>
                <div id="invoice-batch-list"></div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 offset-md-6 text-end">
                    <p>Subtotal: <strong id="subtotal">Rp 0,00</strong></p>
                    <p>Diskon: <strong id="discount">Rp 0,00</strong></p>
                    <p>PPN: <strong id="ppn">Rp 0,00</strong></p>
                    <hr>
                    <h4>Grand Total: <strong id="grand-total">Rp 0,00</strong></h4>
                    <input type="hidden" name="grand_total_value">
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Buat Invoice</button>
        </div>
    </form>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        window.siteUrl = "<?= site_url('/') ?>";
    </script>
    <?php include('Js/index_js.php'); ?>
<?= $this->endSection() ?>