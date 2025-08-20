<script>
$(document).ready(function() {
    let calculatedTotals = null;
    const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' });

    // Initialize DataTable once with a placeholder
    let itemsTable = $('#quotation-items-table').DataTable({
        paging: false,
        searching: false,
        info: false,
        "language": {
            "emptyTable": "Pilih kontrak untuk melihat item."
        }
    });

    function resetUI() {
        $('#details-section').hide();
        $('#invoice-batch-list, #quotation-details').html('');
        itemsTable.clear().draw(); // Clear the DataTable
        $('#subtotal, #discount, #ppn, #grand-total').text('Rp 0,00');
        $('input[name="grand_total_value"]').val(0);
        calculatedTotals = null;
    }

    $('#contract-selector').on('change', function() {
        let contract_uuid = $(this).val();
        if (!contract_uuid) {
            resetUI();
            return;
        }

        $.ajax({
            url: window.siteUrl + "finance/invoice/loadInvoiceData",
            type: "POST",
            data: { contract_uuid: contract_uuid },
            dataType: "JSON",
            success: function(data) {
                calculatedTotals = data.calculated_totals; 
                
                let detailsHtml = `<div class="col-md-6"><p><strong>Term of Payment:</strong> ${data.quotation.mkt_quotation_term_of_payment} Bulan</p></div>`;
                $('#quotation-details').html(detailsHtml);

                // Clear and add new data to DataTable
                itemsTable.clear();
                data.order_items.forEach(item => {
                    let lokasi = (item.building_name && item.room_name) ? `(${item.building_name} - ${item.room_name})` : '';
                    itemsTable.row.add([
                        `${item.inventory_name} ${lokasi}`,
                        item.mkt_quotation_order_item_qty,
                        formatter.format(item.mkt_quotation_order_item_price),
                        formatter.format(item.mkt_quotation_order_item_qty * item.mkt_quotation_order_item_price)
                    ]).draw(false);
                });

                let serviceHtml = '';
                if (data.invoice_batches.length > 0) {
                    data.invoice_batches.forEach(batch => {
                        serviceHtml += `<div class="form-check"><input class="form-check-input service-checkbox" type="checkbox" name="services_batch[]" value="${batch.value}"><label class="form-check-label">${batch.label}</label></div>`;
                    });
                } else {
                    serviceHtml = '<p class="text-muted">Tidak ada Job Service yang siap ditagih.</p>';
                }
                $('#invoice-batch-list').html(serviceHtml);
                
                let nextInvoiceNumber = data.invoice_count + 1;
                $('#invoice-number-display').text(`Ini akan menjadi Invoice ke-${nextInvoiceNumber} untuk kontrak ini.`);

                resetTotals();
                $('#details-section').show();
            },
            error: function() { resetUI(); }
        });
    });

    $(document).on('change', '.service-checkbox', function() {
        updateTotals();
    });

    function updateTotals() {
        if (!calculatedTotals) return;
        let checkedCount = $('.service-checkbox:checked').length;
        if (checkedCount === 0) {
            resetTotals();
            return;
        }
        
        let grandTotalPerInvoice = parseFloat(calculatedTotals.grand_total);
        let totalInvoiceValue = grandTotalPerInvoice * checkedCount;
        
        $('#subtotal').text(formatter.format(parseFloat(calculatedTotals.subtotal) * checkedCount));
        $('#discount').text(formatter.format(parseFloat(calculatedTotals.discount) * checkedCount));
        $('#ppn').text(formatter.format(parseFloat(calculatedTotals.ppn) * checkedCount));
        $('#grand-total').text(formatter.format(totalInvoiceValue));
        $('input[name="grand_total_value"]').val(totalInvoiceValue);
    }
    
    function resetTotals() {
        $('#subtotal, #discount, #ppn, #grand-total').text('Rp 0,00');
        $('input[name="grand_total_value"]').val(0);
    }

    $('#invoice-form').on('submit', function(e) {
        e.preventDefault();
        let checkedServices = $('.service-checkbox:checked').length;
        if (checkedServices === 0) {
            alert('Harap pilih setidaknya satu paket invoice untuk ditagih.');
            return;
        }

        let formData = {
            contract_uuid: $('#contract-selector').val(),
            invoice_date: $('#invoice_date').val(),
            services_batch: $('.service-checkbox:checked').map(function() { return $(this).val(); }).get(),
            grand_total_value: $('input[name="grand_total_value"]').val()
        };

        $.ajax({
            url: window.siteUrl + "finance/invoice/store",
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'JSON',
            success: function(response) {
                if(response.status === 'success') {
                    alert(response.message);
                    window.location.reload(); 
                } else {
                    alert('Gagal menyimpan: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan pada server.');
            }
        });
    });
});
</script>