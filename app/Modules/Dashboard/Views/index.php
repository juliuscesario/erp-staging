<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Dashboard Utama
<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
    <style>
        .dashboard-card {
            display: block;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease-in-out;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
        .dashboard-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 180px;
        }
        .dashboard-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .dashboard-card h4 {
            font-size: 1.5rem;
        }
    </style>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="text-center mb-5">
        <h1>Selamat Datang, Julius!</h1>
        <p class="lead text-muted">Silakan pilih modul untuk memulai.</p>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="<?= site_url('warehouse/materialrequest') ?>" class="card shadow-sm dashboard-card h-100">
                <div class="card-body">
                    <i class="fas fa-boxes-stacked"></i>
                    <h4>Material Request</h4>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="<?= site_url('operational/opr_service') ?>" class="card shadow-sm dashboard-card h-100">
                <div class="card-body">
                    <i class="fas fa-wrench"></i>
                    <h4>Job Service</h4>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="<?= site_url('finance/invoice/list') ?>" class="card shadow-sm dashboard-card h-100">
                <div class="card-body">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h4>Invoice</h4>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="<?= site_url('fpayment/payment') ?>" class="card shadow-sm dashboard-card h-100">
                <div class="card-body">
                    <i class="fas fa-money-check-dollar"></i>
                    <h4>Payment</h4>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="<?= site_url('jurnal/jurnal') ?>" class="card shadow-sm dashboard-card h-100">
                <div class="card-body">
                    <i class="fas fa-book"></i>
                    <h4>Jurnal</h4>
                </div>
            </a>
        </div>
    </div>
<?= $this->endSection() ?>