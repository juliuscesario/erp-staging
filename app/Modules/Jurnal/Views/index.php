<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Jurnal Transaksi
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Jurnal Transaksi (data_2025_trans)</h1>
        <a href="<?= site_url('/dashboard/home') ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="jurnal-table" class="table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>No. Transaksi</th>
                        <th>Keterangan</th>
                        <th>Akun</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        window.siteUrl = "<?= site_url('/') ?>";
    </script>
    <?php include('Js/index_js.php'); ?>
<?= $this->endSection() ?>