<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Daftar Invoice
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Invoice</h1>
        <a href="<?= site_url('finance/invoice') ?>" class="btn btn-primary">
            + Buat Invoice Baru
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="invoice-table" class="table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tanggal</th>
                        <th>Nama Pelanggan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('finance/invoice/detail/' . $invoice['inv_invoice_uuid']) ?>">
                                        <?= esc($invoice['inv_invoice_no']) ?>
                                    </a>
                                </td>
                                <td><?= date('d M Y', strtotime($invoice['inv_invoice_date'])) ?></td>
                                <td><?= esc($invoice['cust_name']) ?></td>
                                <td>
                                    <?php 
                                        $badge_class = ($invoice['inv_invoice_status'] === 'Paid') ? 'bg-success' : 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= esc($invoice['inv_invoice_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= site_url('finance/invoice/printPdf/' . $invoice['inv_invoice_uuid']) ?>" target="_blank" class="btn btn-sm btn-info">
                                        Print
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        $(document).ready(function() {
            $('#invoice-table').DataTable({
                "order": [] // Nonaktifkan pengurutan default
            });
        });
    </script>
<?= $this->endSection() ?>