<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Payment
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Invoice (Payment)</h1>
        <a href="<?= site_url('/dashboard/home') ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>


    <div class="card">
        <div class="card-body">
            <table id="payment-table" class="table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tanggal Invoice</th>
                        <th>Jatuh Tempo</th>
                        <th>Nama Pelanggan</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <?php
                                $is_overdue = strtotime($invoice['inv_invoice_due_date']) < time() && $invoice['inv_invoice_status'] == 'Unpaid';
                            ?>
                            <tr class="<?= $is_overdue ? 'table-danger' : '' ?>">
                                <td>
                                    <a href="<?= site_url('finance/invoice/detail/' . $invoice['inv_invoice_uuid']) ?>">
                                        <?= esc($invoice['inv_invoice_no']) ?>
                                    </a>
                                </td>
                                <td><?= date('d M Y', strtotime($invoice['inv_invoice_date'])) ?></td>
                                <td><?= date('d M Y', strtotime($invoice['inv_invoice_due_date'])) ?></td>
                                <td><?= esc($invoice['cust_name']) ?></td>
                                <td><?= number_format($invoice['inv_invoice_grand_total'], 0, ',', '.') ?></td>
                                <td>
                                    <?php
                                        $badge_class = ($invoice['inv_invoice_status'] === 'Paid') ? 'bg-success' : 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= esc($invoice['inv_invoice_status']) ?>
                                    </span>
                                    <?php if ($is_overdue): ?>
                                        <span class="badge bg-danger">OVERDUE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($invoice['inv_invoice_status'] == 'Unpaid'): ?>
                                        <button type="button" class="btn btn-sm btn-primary pay-button" data-uuid="<?= $invoice['inv_invoice_uuid'] ?>" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                            Bayar
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    </div>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        $(document).ready(function() {
            $('#payment-table').DataTable({ "order": [] });

            $('.pay-button').on('click', function() {
                let invoiceUUID = $(this).data('uuid');
                let modalBody = $('#paymentModal .modal-body');
                
                modalBody.html('<p class="text-center">Loading...</p>');
                
                // Panggil controller untuk mengambil form create via AJAX
                modalBody.load("<?= site_url('fpayment/payment/create/') ?>" + invoiceUUID);
            });
        });
    </script>
<?= $this->endSection() ?>