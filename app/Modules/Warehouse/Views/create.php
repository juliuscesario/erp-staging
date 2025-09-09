<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <style>.signature-canvas { border: 1px solid #ccc; }</style>
</head>
<body class="container mt-4">
   <h3>Create Material Request</h3>
    <a href="<?= site_url('warehouse/materialrequest') ?>" class="btn btn-secondary">&laquo; Kembali</a>

    <form id="material-request-form" class="mt-3">
        <input type="hidden" name="matrequest_uuid" value="<?= esc($new_matrequest_uuid) ?>">
        <div class="mb-3">
            <label for="schedule-selector" class="form-label">Pilih Jadwal</label>
            <div class="input-group">
                <select id="schedule-selector" name="schedule_uuid" class="form-select">
                     <option value="">-- Pilih Jadwal --</option>
                    <?php if (!empty($schedules)): ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <option value="<?= esc($schedule['opr_schedule_uuid']) ?>">
                                Jadwal #<?= esc($schedule['opr_schedule_id']) ?> (Tgl: <?= esc($schedule['opr_schedule_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Semua jadwal untuk kontrak ini sudah dibuatkan MR</option>
                    <?php endif; ?>
                </select>
                <button id="generate-btn" class="btn btn-info" type="button" style="display:none;">Generate Items</button>
            </div>
        </div>

        <hr>
        <h5>Item yang Dibutuhkan</h5>
        <table id="item-table" class="table table-bordered" style="width:100%"></table>

        <hr>
        <h5>Tanda Tangan</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6>Diserahkan oleh (Gudang)</h6>
                <select name="kar_uuid_1" class="form-select mb-2">
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach ($karyawan as $kar): ?><option value="<?= esc($kar['kar_uuid']) ?>"><?= esc($kar['kar_name']) ?></option><?php endforeach; ?>
                </select>
                <canvas id="signature-pad-1" class="signature-canvas" width="400" height="200"></canvas><br>
                <button type="button" class="btn btn-secondary btn-sm" id="clear-1">Clear</button>
                <input type="hidden" name="signature_data_1" id="signature-data-1">
            </div>
            <div class="col-md-6 mb-3">
                <h6>Diterima oleh (Teknisi)</h6>
                <select name="kar_uuid_2" class="form-select mb-2">
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach ($karyawan as $kar): ?><option value="<?= esc($kar['kar_uuid']) ?>"><?= esc($kar['kar_name']) ?></option><?php endforeach; ?>
                </select>
                <canvas id="signature-pad-2" class="signature-canvas" width="400" height="200"></canvas><br>
                <button type="button" class="btn btn-secondary btn-sm" id="clear-2">Clear</button>
                <input type="hidden" name="signature_data_2" id="signature-data-2">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary mt-3">Simpan</button>
    </form>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        let itemTable;
        const canvas1 = document.getElementById('signature-pad-1');
        const signaturePad1 = new SignaturePad(canvas1);
        const canvas2 = document.getElementById('signature-pad-2');
        const signaturePad2 = new SignaturePad(canvas2);

        $(document).ready(function() {
            itemTable = $('#item-table').DataTable({
                paging: false, info: false, searching: false,
                "columns": [
                    { "data": null, "title": "No" }, { "data": "kode", "title": "Kode" },
                    { "data": "nama", "title": "Nama Barang" }, { "data": "lokasi", "title": "Lokasi" },
                    { "data": "qty", "title": "Qty" }, { "data": "qrcode", "title": "QR Code", "render": (d) => d ? `<img src="${d}" width="80">` : '-' }
                ],
                "columnDefs": [ { "orderable": false, "targets": [0, 5] } ],
                "createdRow": (row, data) => $(row).data('item-data', data),
                "drawCallback": function (settings) {
                    this.api().column(0, {page:'current'}).nodes().each((cell, i) => cell.innerHTML = i + 1);
                }
            });

            $('#clear-1').on('click', () => signaturePad1.clear());
            $('#clear-2').on('click', () => signaturePad2.clear());

            $('#schedule-selector').on('change', function() {
                let selected = $(this).val();
                itemTable.clear().draw();
                if (selected) $('#generate-btn').show(); else $('#generate-btn').hide();
            });

            $('#generate-btn').on('click', function() {
                 let btn = $(this);
                btn.prop('disabled', true).text('Loading...');
                $.ajax({
                    url: "<?= site_url('warehouse/materialrequest/generate_items') ?>", type: "POST",
                    data: { schedule_uuid: $('#schedule-selector').val(), matrequest_uuid: $('[name="matrequest_uuid"]').val() },
                    dataType: "JSON",
                    success: function(items) { 
                        itemTable.clear().rows.add(items).draw(); 
                        btn.prop('disabled', false).text('Generate Items');
                    },
                    error: function() { 
                        alert('Gagal memuat item!'); 
                        btn.prop('disabled', false).text('Generate Items');
                    }
                });
            });

            $('#material-request-form').on('submit', function(e) {
                e.preventDefault();
                if (signaturePad1.isEmpty() || signaturePad2.isEmpty()) { alert("Tanda tangan harus diisi."); return; }
                
                $('#signature-data-1').val(signaturePad1.toDataURL());
                $('#signature-data-2').val(signaturePad2.toDataURL());

                let formData = $(this).serializeArray();
                let submissionData = {};
                $(formData).each((i, obj) => { submissionData[obj.name] = obj.value; });
                
                submissionData.items = itemTable.rows().data().toArray();

                $.ajax({
                    url: "<?= site_url('warehouse/materialrequest/store') ?>", type: 'POST',
                    data: JSON.stringify(submissionData), contentType: 'application/json', dataType: 'JSON',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('Data berhasil disimpan!');
                            window.location.href = "<?= site_url('warehouse/materialrequest') ?>";
                        } else { alert('Gagal: ' + (response.message || 'Unknown error')); }
                    },
                    error: function() { alert('Error pada server.'); }
                });
            });
        });
    </script>
</body>
</html>