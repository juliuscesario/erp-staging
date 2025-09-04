<script>
$(document).ready(function() {
    //variable yang dipakai terus
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas);
    let itemTable = null;
    let current_cust_uuid = null; // Simpan UUID customer saat ini
    
    $('#clear-signature').on('click', function(){
        signaturePad.clear();
    });

    $(document).on('change', '.photo-input', function() {
        let fileInput = this;
        let hiddenInput = $(this).siblings('.photo-base64-input');

        if (fileInput.files && fileInput.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                // Simpan hasil Base64 ke hidden input
                hiddenInput.val(e.target.result);
            }
            reader.readAsDataURL(fileInput.files[0]);
        }
    });
    

    $('#schedule-selector').on('change', function() {
        let selectedSchedule = $(this).val();
        $('#details-section').hide(); // Selalu sembunyikan detail saat pilihan berubah

        if (selectedSchedule === '') {
            return;
        }

        // Panggilan AJAX Tahap 1: Validasi Jadwal
        $.ajax({
            url: window.siteUrl + "operational/opr_service/validateSchedule",
            type: "POST",
            data: { schedule_uuid: selectedSchedule },
            dataType: "JSON",
            success: function(validationResponse) {
                if (validationResponse.isValid) {
                    // Jika valid, lanjutkan ke Tahap 2: Ambil semua data
                    $.ajax({
                        url: window.siteUrl+ "operational/opr_service/loadServiceData",
                        type: 'POST',
                        data: { schedule_uuid: selectedSchedule },
                        dataType: 'JSON',
                        success: function(data) {
                            current_cust_uuid = data.main.cust_uuid; // <-- SIMPAN UUID CUSTOMER
                            if (!data.main) {
                                alert('Data untuk jadwal ini tidak ditemukan!');
                                $('#details-section').hide();
                                return;
                            }
                            
                            // Isi bagian header
                            $('#cust-name').text(data.main.cust_name);
                            $('#contract-no').text(data.main.mkt_contract_no);
                            $('#service-day').text(data.main.service_day);
                            $('#service-period-full').text(data.main.service_period_full);

                            // Gabungkan alamat gedung dari data utama
                            let alamat_lengkap = [
                                data.main.building_add1, data.main.building_kelurahan, data.main.building_kecamatan, 
                                data.main.building_kabupaten, data.main.building_kode_pos
                            ].filter(Boolean).join(', ');
                            $('#cust-address').text(alamat_lengkap);

                            // 1. destroy data table dulu 
                            if (itemTable) {
                                itemTable.destroy();
                            }

                            // Isi tabel checklist
                            let checklistHtml = '';
                            data.assets.forEach((asset, index) => {
                                // Simpan data building & room sebagai data attribute di <tr>
                                checklistHtml += `<tr 
                                    data-inventory-uuid="${asset.wr_matrequest_item_inventory_uuid}"
                                    data-building-uuid="${asset.mkt_quotation_order_building_uuid}"
                                    data-room-uuid="${asset.mkt_quotation_order_room_uuid}">
                                    <td>${index + 1}</td>
                                    <td>${asset.room_name || 'N/A'}</td>
                                    <td>${asset.inventory_name} (${asset.inventory_kode})</td>
                                    <td><input type="text" name="problem" class="form-control"></td>
                                    <td><input type="text" name="action" class="form-control"></td>
                                    <td><input type="text" name="work_duration" class="form-control"></td>
                                    <td>
                                        <input type="file" class="form-control photo-input" data-uuid="${asset.wr_matrequest_item_inventory_uuid}">
                                        <input type="hidden" name="items[${asset.wr_matrequest_item_inventory_uuid}][photo_base64]" class="photo-base64-input">
                                    </td>
                                </tr>`;
                            });

                            $('#checklist-body').html(checklistHtml);

                             // 4. Tunda inisialisasi DataTables untuk memberi waktu pada browser
                            setTimeout(function() {
                                itemTable = $('#checklist-table').DataTable({
                                    "responsive": true,
                                    "paging": false,
                                    "searching": false,
                                    "info": false,
                                    "order": []
                                });
                                
                                // Sesuaikan ulang kolom setelah inisialisasi (sebagai pengaman tambahan)
                                itemTable.columns.adjust().responsive.recalc();

                            }, 5); // Jeda 0 milidetik
                            
                            // Simpan data PIC ke variabel global
                            allPicsData = data.pics;

                            // Isi dropdown PIC
                            let picHtml = '<option value="">Pilih PIC</option>';
                            allPicsData.forEach(pic => {
                                let nama_pic = [pic.cust_pic_panggilan, pic.cust_pic_kontak].filter(Boolean).join(' ');
                                picHtml += `<option value="${pic.cust_pic_uuid}">${nama_pic}</option>`;
                            });
                            $('#pic-selector').html(picHtml);

                            // Reset tampilan jabatan
                            $('#pic-position-display').text('-');
                            $('#pic-position-hidden').val('');

                            signaturePad.clear();
                            $('#details-section').show();

                            
                        },
                        error: function() {
                            alert('Gagal mengambil detail data servis.');
                        }
                    });
                } else {
                    // Jika tidak valid, tampilkan pesan error dan berhenti
                    alert(validationResponse.message);
                }
            },
            error: function() {
                alert('Gagal melakukan validasi jadwal ke server.');
            }
        });
    });

    // Ganti event listener untuk dropdown PIC
    $('#pic-selector').on('change', function() {
        let selectedPicUuid = $(this).val();
        let position = '-';
        let name = ''; // Variabel untuk nama

        if (selectedPicUuid) {
            let selectedPic = allPicsData.find(pic => pic.cust_pic_uuid === selectedPicUuid);
            if (selectedPic) {
                // Gabungkan panggilan dan nama
                name = [selectedPic.cust_pic_panggilan, selectedPic.cust_pic_kontak].filter(Boolean).join(' ');
                position = selectedPic.cust_pic_position || '-';
            }
        }
        
        // Update tampilan dan KEDUA hidden input
        $('#pic-position-display').text(position);
        $('#pic-position-hidden').val(position);
        $('#pic-name-hidden').val(name); // <-- BARU
    });

    //SUBMIT FORMNYA
    $('#job-service-form').on('submit', function(e) {
        e.preventDefault();
        
        // VALIDASI BARU
        if ($('#pic-selector').val() === '') {
            alert("Harap pilih nama PIC penanda tangan.");
            return;
        }
        
        $('#signature-data').val(signaturePad.toDataURL());

        // Gunakan FormData untuk mengumpulkan semua input
        let formData = new FormData(this);

        // Ambil data item dari tabel dan tambahkan ke FormData dengan index unik
        let item_counter = 0;
        $('#checklist-body tr').each(function() {
            let row = $(this);
            let inv_uuid = row.data('inventory-uuid');
            
            // Kirim inventory_uuid sebagai bagian dari data item
            formData.append(`items[${item_counter}][inventory_uuid]`, inv_uuid);
            
            // Tambahkan data building dan room ke form
            formData.append(`items[${item_counter}][building_uuid]`, row.data('building-uuid'));
            formData.append(`items[${item_counter}][room_uuid]`, row.data('room-uuid'));

            // Tambahkan data input dari setiap baris
            formData.append(`items[${item_counter}][problem]`, row.find('input[name="problem"]').val());
            formData.append(`items[${item_counter}][action]`, row.find('input[name="action"]').val());
            formData.append(`items[${item_counter}][work_duration]`, row.find('input[name="work_duration"]').val());
            // Kirim data base64 dari hidden input
            formData.append(`items[${item_counter}][photo_base64]`, row.find('.photo-base64-input').val());

            item_counter++;
        });

        // Kirim data via AJAX menggunakan FormData
        $.ajax({
            url: window.siteUrl + "operational/opr_service/store",
            type: 'POST',
            data: formData,
            processData: false, // Penting: jangan proses datanya
            contentType: false, // Penting: biarkan browser mengatur content type
            dataType: 'JSON',
            success: function(response) {
                if(response.status === 'success') {
                    alert(response.message);
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

    // Event saat tombol 'Tambah PIC Baru' diklik
    $('[data-bs-target="#addPicModal"]').on('click', function() {
        if (!current_cust_uuid) {
            alert('Pilih jadwal terlebih dahulu!');
            return;
        }
        // Ambil form dari controller CustPic dan masukkan ke modal
        $('#new-pic-form-container').load(window.siteUrl + "master/custpic/create/" + current_cust_uuid);
    });

    // Event saat form di dalam MODAL di-submit
    $(document).on('submit', '#new-pic-form', function(e) {
        e.preventDefault();
        let form = $(this);
        let url = form.attr('action');

        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            dataType: "JSON",
            success: function(response) {
                if (response.status === 'success') {
                    $('#addPicModal').modal('hide'); // Tutup modal
                    // Segarkan dropdown PIC dan pilih yang baru
                    refreshPicDropdown(response.new_pic.cust_pic_uuid);
                } else {
                    alert('Gagal menyimpan PIC baru.');
                }
            }
        });
    });
    
    function refreshPicDropdown(select_uuid = null) {
        if (!current_cust_uuid) return;
        
        $.ajax({
            url: window.siteUrl+ "operational/opr_service/loadServiceData",
            type: 'POST',
            data: { schedule_uuid: $('#schedule-selector').val() },
            dataType: 'JSON',
            success: function(data) {
                // --- TAMBAHKAN BARIS INI UNTUK SINKRONISASI ---
                allPicsData = data.pics; // Perbarui variabel global dengan data terbaru

                let picHtml = '<option value="">Pilih PIC</option>';
                data.pics.forEach(pic => {
                    let nama_pic = [pic.cust_pic_panggilan, pic.cust_pic_kontak].filter(Boolean).join(' ');
                    picHtml += `<option value="${pic.cust_pic_uuid}">${nama_pic}</option>`;
                });
                $('#pic-selector').html(picHtml);

                if(select_uuid) {
                    $('#pic-selector').val(select_uuid);
                    $('#pic-selector').trigger('change');
                }
            }
        });
    }
});
</script>