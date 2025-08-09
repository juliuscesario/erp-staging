<script>
$(document).ready(function() {
    $('#service-table').DataTable({
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "order": [],
        "ajax": {
            "url": window.siteUrl + "operational/opr_service/load_list",
            "type": "POST"
        },
        "columns": [
            { "data": "opr_service_no" },
            { "data": "opr_service_date" },
            { "data": "cust_name" },
            { "data": "opr_schedule_period_run" },
            { "data": "opr_service_status" },
            { "data": "opr_service_uuid" }
        ],
        "columnDefs": [
            {
                "targets": 0,
                "render": function(data, type, row) {
                    return `<a href="${window.siteUrl}operational/opr_service/detail/${row.opr_service_uuid}">${data}</a>`;
                }
            },
            {
                "targets": 3,
                "render": function(data, type, row) {
                    return `${row.opr_schedule_period_run} dari ${row.opr_schedule_period_total}`;
                }
            },
            {
                "targets": 4,
                "render": function(data, type, row) {
                    let badge_class = (data === 'Done') ? 'bg-success' : 'bg-warning';
                    return `<span class="badge ${badge_class}">${data}</span>`;
                }
            },
            {
                "targets": 5,
                "orderable": false,
                "searchable": false,
                "render": function(data, type, row) {
                    return `<a href="${window.siteUrl}operational/opr_service/edit/${data}" class="btn btn-sm btn-warning">Edit</a>`;
                }
            }
        ]
    });
});
</script>