<script>
    $(document).ready(function() {
        Fancybox.bind("[data-fancybox]", {
          // Opsi kustom jika ada
        });
        $('#checklist-table').DataTable({
            "responsive": true,
            "order": [],        // Nonaktifkan pengurutan default
            "searching": false, // Opsi-opsi ini membuat tampilan lebih bersih
            "paging": false,    // untuk halaman detail
            "info": false
        });
    });
</script>