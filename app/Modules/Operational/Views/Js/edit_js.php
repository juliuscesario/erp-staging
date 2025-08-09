<script>
$(document).ready(function() {
    $('#checklist-table').DataTable({
        "responsive": true,
        "order": [],
        "searching": false,
        "paging": false,
        "info": false
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
    // Inisialisasi variabel dari data yang dikirim oleh PHP di view utama
    let allPicsData = window.picsData;
    let current_cust_uuid = window.serviceData.cust_uuid;
    let current_schedule_uuid = window.serviceData.opr_service_schedule_uuid;

    // Inisialisasi Signature Pad
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas);

    $('#clear-signature').on('click', function() {
        signaturePad.clear();
    });

    // Event saat dropdown PIC berubah untuk update jabatan
    $('#pic-selector').on('change', function() {
        let selectedPicUuid = $(this).val();
        let position = '-';
        let name = '';

        if (selectedPicUuid) {
            let selectedPic = allPicsData.find(pic => pic.cust_pic_uuid === selectedPicUuid);
            if (selectedPic) {
                name = [selectedPic.cust_pic_panggilan, selectedPic.cust_pic_kontak].filter(Boolean).join(' ');
                position = selectedPic.cust_pic_position || '-';
            }
        }
        
        $('#pic-position-display').text(position);
        $('#pic-position-hidden').val(position);
        $('#pic-name-hidden').val(name);
        // Sembunyikan gambar tanda tangan lama dan bersihkan kanvas jika PIC diganti
        $('#saved-signature').hide(); 
        signaturePad.clear();
    });

    // Event saat tombol 'Tambah PIC Baru' diklik untuk membuka modal
    $('[data-bs-target="#addPicModal"]').on('click', function() {
        if (!current_cust_uuid) {
            alert('Customer UUID tidak ditemukan!');
            return;
        }
        // Ambil form dari controller CustPic dan masukkan ke dalam modal
        $('#new-pic-form-container').load(window.siteUrl + "master/custpic/create/" + current_cust_uuid);
    });

    // Event saat form di dalam MODAL di-submit
    $(document).on('submit', '#new-pic-form', function(e) {
        e.preventDefault();
        let form = $(this);
        $.ajax({
            type: "POST",
            url: form.attr('action'),
            data: form.serialize(),
            dataType: "JSON",
            success: function(response) {
                if (response.status === 'success') {
                    $('#addPicModal').modal('hide');
                    refreshPicDropdown(response.new_pic.cust_pic_uuid);
                } else {
                    alert('Gagal menyimpan PIC baru.');
                }
            }
        });
    });
    
    // Fungsi untuk menyegarkan dropdown PIC setelah penambahan baru
    function refreshPicDropdown(select_uuid = null) {
        if (!current_cust_uuid) return;
        
        // Panggil kembali loadServiceData untuk mendapatkan daftar PIC terbaru
        $.ajax({
            url: window.siteUrl + "operational/opr_service/loadServiceData",
            type: 'POST',
            data: { schedule_uuid: current_schedule_uuid }, // Gunakan schedule_uuid yang sudah disimpan
            dataType: 'JSON',
            success: function(data) {
                allPicsData = data.pics; // Perbarui data PIC global

                let picHtml = '<option value="">Pilih PIC</option>';
                data.pics.forEach(pic => {
                    let nama_pic = [pic.cust_pic_panggilan, pic.cust_pic_kontak].filter(Boolean).join(' ');
                    picHtml += `<option value="${pic.cust_pic_uuid}">${nama_pic}</option>`;
                });
                $('#pic-selector').html(picHtml);

                if(select_uuid) {
                    $('#pic-selector').val(select_uuid).trigger('change'); // Pilih PIC baru & picu event change
                }
            }
        });
    }

    // Event saat form utama di-submit (ke fungsi update)
    $('#job-service-form').on('submit', function(e) {
        // Hanya isi hidden input jika kanvas TIDAK kosong (ada ttd baru)
        if (!signaturePad.isEmpty()) {
            $('#signature-data').val(signaturePad.toDataURL());
        }
    });
    //FANCYBOX
    Fancybox.bind("[data-fancybox]", {
        // Opsi kustom jika ada
    });
});
</script>