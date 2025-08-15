<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Daftar Invoice
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Invoice</h1>
        <div>
            <a href="<?= site_url('/dashboard/home') ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
            <a href="<?= site_url('finance/invoice') ?>" class="btn btn-primary">
                + Buat Invoice Baru
            </a>
        </div>
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
                        <?php 
                            // Variabel untuk tanggal hari ini
                            $currentDate = new DateTime();
                            $currentDay = (int)$currentDate->format('j');
                            $currentMonthYear = $currentDate->format('Y-m');
                        ?>
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
                                    <?php 
                                        // Ambil bulan dan tahun dari tanggal invoice
                                        $invoiceDate = new DateTime($invoice['inv_invoice_date']);
                                        $invoiceMonthYear = $invoiceDate->format('Y-m');

                                        // Logika baru untuk menonaktifkan tombol edit
                                        $isLocked = ($currentDay >= 14 && $invoiceMonthYear < $currentMonthYear);
                                        $can_edit = ($invoice['inv_invoice_status'] === 'Unpaid' && !$isLocked);
                                    ?>
                                    <a href="<?= $can_edit ? site_url('finance/invoice/edit/' . $invoice['inv_invoice_uuid']) : '#' ?>" 
                                    class="btn btn-sm btn-warning <?= $can_edit ? '' : 'disabled' ?>"
                                    <?= !$can_edit ? 'title="Invoice dari bulan sebelumnya tidak dapat diubah setelah tanggal 14."' : '' ?>>
                                        Edit
                                    </a>
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