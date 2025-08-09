<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Daftar Job Service
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Job Service</h1>
        <a href="<?= site_url('operational/opr_service/create/b11a7816-01a0-4946-8253-d557f0d2dfc1') ?>" class="btn btn-primary">
            + Buat Job Service Baru
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="service-table" class="table table-striped dt-responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No. Service</th>
                        <th>Tanggal</th>
                        <th>Nama Pelanggan</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Aksi</th>
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
    <?php include('Js/list_js.php'); ?>
<?= $this->endSection() ?>