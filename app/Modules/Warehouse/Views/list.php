<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Material Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Daftar Material Request</h1>
        <div>
            <a href="<?= site_url('/dashboard/home') ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
            <a href="<?= site_url('/warehouse/materialrequest/create/b11a7816-01a0-4946-8253-d557f0d2dfc1') ?>" class="btn btn-primary">
                + Tambah Baru untuk CA-ART/07-25/0002
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="mr-table" class="table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>No. MR</th>
                        <th>Tanggal</th>
                        <th>No. Kontrak</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($material_requests)): ?>
                        <?php foreach ($material_requests as $mr): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('/warehouse/materialrequest/detail/' . $mr['wr_matrequest_uuid']) ?>">
                                        <?= esc($mr['wr_matrequest_no']) ?>
                                    </a>
                                </td>
                                <td><?= esc($mr['wr_matrequest_date']) ?></td>
                                <td><?= esc($mr['mkt_contract_no']) ?></td>
                                <td><span class="badge bg-warning"><?= esc($mr['wr_matrequest_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#mr-table').DataTable({
                "order": [] // Nonaktifkan pengurutan default
            });
        });
    </script>
</body>
</html>