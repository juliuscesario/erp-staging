<script>
$(document).ready(function() {
    $('#jurnal-table').DataTable({
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "order": [[ 0, "desc" ]], // Mengurutkan berdasarkan kolom pertama (ID) secara descending
        "ajax": {
            "url": window.siteUrl + "jurnal/jurnal/load_data",
            "type": "POST"
        },
        "columns": [
            { "data": "trans_id" },
            { "data": "trans_tgl" },
            { "data": "trans_no_trans" },
            { "data": "trans_ket" },
            { "data": "nama_acc" },
            { "data": "trans_DB" },
            { "data": "trans_CR" }
        ],
        "columnDefs": [
            {
                "targets": 0, // ID
                "width": "5%"
            },
            {
                "targets": 1, // Tanggal
                "render": function(data, type, row) {
                    return new Date(data).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                }
            },
            {
                "targets": 4, // Akun
                "render": function(data, type, row) {
                    return `(${row.trans_no_acc}) ${data}`;
                }
            },
            {
                "targets": [5, 6], // Debit & Kredit
                "className": "text-end",
                "render": function(data, type, row) {
                    return parseFloat(data) > 0 ? new Intl.NumberFormat('id-ID').format(data) : '-';
                }
            }
        ]
    });
});
</script>