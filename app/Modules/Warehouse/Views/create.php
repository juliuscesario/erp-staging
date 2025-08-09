<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <style>
        .signature-canvas { border: 1px solid #ccc; border-radius: 0.25rem; }
    </style>
</head>
<body class="container mt-4">

   <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Create Material Request</h3>
        <a href="<?= site_url('warehouse/materialrequest') ?>" class="btn btn-secondary">
            &laquo; Kembali ke Daftar
        </a>
    </div>

    <form id="material-request-form">
        <div class="mb-3">
            <label for="schedule-selector" class="form-label">Pilih Jadwal</label>
            <div class="input-group">
                <select id="schedule-selector" name="schedule_uuid" class="form-select">
                    <option value="">-- Pilih Jadwal --</option>
                    <?php if (!empty($schedules)): ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <option value="<?= esc($schedule['opr_schedule_uuid']) ?>">
                                Jadwal #<?= esc($schedule['opr_schedule_id']) ?> (Tanggal: <?= esc($schedule['opr_schedule_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button id="generate-btn" class="btn btn-info" type="button">Generate Items</button>
            </div>
        </div>

        <hr>
        <h2>Item yang Dibutuhkan</h2>
        <table id="item-table" class="table table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>QR Code</th>
                </tr>
            </thead>
            <tbody id="material-item-list">
                </tbody>
        </table>

        <hr>
        <h2>Tanda Tangan</h2>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h5>Diserahkan oleh (Gudang)</h5>
                <select name="kar_uuid_1" class="form-select mb-2 employee-selector" data-target="1">
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach ($karyawan as $kar): ?>
                        <option value="<?= esc($kar['kar_uuid']) ?>"><?= esc($kar['kar_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <canvas id="signature-pad-1" class="signature-canvas" width="400" height="200"></canvas>
                <br>
                <button type="button" class="btn btn-secondary btn-sm clear-btn" data-target="1">Clear</button>
                <input type="hidden" name="signature_data_1" id="signature-data-1">
            </div>
            <div class="col-md-6 mb-3">
                <h5>Diterima oleh (Teknisi)</h5>
                <select name="kar_uuid_2" class="form-select mb-2 employee-selector" data-target="2">
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach ($karyawan as $kar): ?>
                        <option value="<?= esc($kar['kar_uuid']) ?>"><?= esc($kar['kar_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <canvas id="signature-pad-2" class="signature-canvas" width="400" height="200"></canvas>
                <br>
                <button type="button" class="btn btn-secondary btn-sm clear-btn" data-target="2">Clear</button>
                <input type="hidden" name="signature_data_2" id="signature-data-2">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary mt-3">Simpan Material Request</button>
    </form>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <script>
        $(document).ready(function() {
             // 1. Inisialisasi DataTables SEKALI di awal, dengan kolom yang ditentukan
            let itemTable = $('#item-table').DataTable({
                "destroy": true, // Izinkan untuk re-inisialisasi jika perlu
                "columns": [
                    { "data": null, "title": "No" }, // Kolom untuk nomor urut
                    { "data": "kode", "title": "Kode Barang" },
                    { "data": "nama", "title": "Nama Barang" },
                    { "data": "qty", "title": "Qty" },
                    { 
                        "data": "qrcode", 
                        "title": "QR Code",
                        "render": function(data, type, row) {
                            if (data) {
                                return `<img src="${data}" alt="QR Code">`;
                            }
                            return '-';
                        }
                    }
                ],
                // Nonaktifkan ordering dan searching pada kolom nomor dan QR
                "columnDefs": [ {
                    "searchable": false,
                    "orderable": false,
                    "targets": [0, 4],
                    "render": function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                } ]
            });

            // Inisialisasi Signature Pads
            const canvas1 = document.getElementById('signature-pad-1');
            const signaturePad1 = new SignaturePad(canvas1);
            const canvas2 = document.getElementById('signature-pad-2');
            const signaturePad2 = new SignaturePad(canvas2);

            $('.clear-btn').on('click', function() {
                let target = $(this).data('target');
                if(target == 1) signaturePad1.clear();
                if(target == 2) signaturePad2.clear();
            });

            // Sembunyikan tombol generate di awal
            $('#generate-btn').hide();

            // Event saat dropdown jadwal berubah
            $('#schedule-selector').on('change', function() {
                let selectedSchedule = $(this).val();
                itemTable.clear().draw(); // kosongkan tabel

                if (selectedSchedule === '') {
                    $('#generate-btn').hide();
                    return;
                }

                //Check Schedule
                $.ajax({
                    url: "<?= site_url('warehouse/materialrequest/check_schedule') ?>",
                    type: "POST",
                    data: { schedule_uuid: selectedSchedule },
                    dataType: "JSON",
                    success: function(response) {
                        if(response.has_items) {
                            $('#generate-btn').show();
                        } else {
                            $('#generate-btn').hide();
                            alert('Jadwal ini tidak memerlukan material request.');
                        }
                    }
                });

                // Panggilan AJAX Pertama: Cek apakah jadwal butuh item
                $.ajax({
                    url: "<?= site_url('warehouse/materialrequest/check_schedule') ?>",
                    type: "POST",
                    data: { schedule_uuid: selectedSchedule },
                    dataType: "JSON",
                    success: function(response) {
                        if (response.has_items) {
                            // Jika butuh item, lanjutkan ke pengecekan kedua
                            
                            // Panggilan AJAX Kedua: Cek apakah MR sudah pernah dibuat
                            $.ajax({
                                url: "<?= site_url('warehouse/materialrequest/checkExistingRequest') ?>",
                                type: "POST",
                                data: { schedule_uuid: selectedSchedule },
                                dataType: "JSON",
                                success: function(existingResponse) {
                                    if (existingResponse.exists) {
                                        // Jika SUDAH ADA, jangan tampilkan tombol & beri peringatan
                                        alert('Material Request untuk jadwal ini sudah pernah dibuat.');
                                        $('#generate-btn').hide();
                                    } else {
                                        // Jika BELUM ADA, tampilkan tombol
                                        $('#generate-btn').show();
                                    }
                                }
                            });

                        } else {
                            // Jika tidak butuh item, sembunyikan tombol
                            $('#generate-btn').hide();
                            alert('Jadwal ini tidak memerlukan material request.');
                        }
                    }
                });
            });

            // LOGIKA BARU UNTUK DROPDOWN KARYAWAN
            $('.employee-selector').on('change', function() {
                // Ambil nilai yang dipilih dari kedua dropdown
                let val1 = $('[name="kar_uuid_1"]').val();
                let val2 = $('[name="kar_uuid_2"]').val();

                // Atur ulang opsi untuk dropdown 2 berdasarkan pilihan 1
                $('[name="kar_uuid_2"] option').each(function() {
                    if (val1 !== '' && $(this).val() === val1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });

                // Atur ulang opsi untuk dropdown 1 berdasarkan pilihan 2
                $('[name="kar_uuid_1"] option').each(function() {
                    if (val2 !== '' && $(this).val() === val2) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            });

            // Event saat tombol generate di-klik
            $('#generate-btn').on('click', function() {
                let selectedSchedule = $('#schedule-selector').val();
                if (selectedSchedule === '') return;

                $.ajax({
                    url: "<?= site_url('warehouse/materialrequest/generate_items') ?>",
                    type: "POST",
                    data: { schedule_uuid: selectedSchedule },
                    dataType: "JSON",
                    success: function(items) {
                        // Gunakan API DataTables untuk update data
                        itemTable.clear();        // Kosongkan tabel
                        itemTable.rows.add(items);  // Tambahkan data JSON baru
                        itemTable.draw();         // Gambar ulang tabel dengan data baru
                    },
                    error: function() {
                        alert('Gagal mengambil data item!');
                    }
                });
            });

            //Store Data 
            $('#material-request-form').on('submit', function(e) {
                e.preventDefault(); // Mencegah form submit cara biasa

                if (signaturePad1.isEmpty() || signaturePad2.isEmpty()) {
                    alert("Harap lengkapi kedua tanda tangan.");
                    return;
                }

                // 1. Ambil data tanda tangan dan masukkan ke hidden input
                $('#signature-data-1').val(signaturePad1.toDataURL());
                $('#signature-data-2').val(signaturePad2.toDataURL());

                let formData = $(this).serializeArray(); // Gunakan serializeArray
                let itemsData = itemTable.rows().data().toArray();
                
                // Gabungkan data form dan data item
                let submissionData = {};
                $(formData).each(function(index, obj){
                    submissionData[obj.name] = obj.value;
                });
                submissionData.items = itemsData;

                // 4. Kirim data via AJAX ke controller 'store'
                $.ajax({
                    url: "<?= site_url('warehouse/materialrequest/store') ?>",
                    type: 'POST',
                    data: JSON.stringify(submissionData), // Kirim sebagai JSON string
                    contentType: 'application/json',
                    dataType: 'JSON',
                    success: function(response) {
                        if(response.status === 'success') {
                            alert('Data Material Request berhasil disimpan!');
                            // Reset form atau redirect halaman
                            window.location.reload(); 
                        } else {
                            alert('Gagal menyimpan data: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan pada server.');
                    }
                });
            });
        });
    </script>
</body>
</html>