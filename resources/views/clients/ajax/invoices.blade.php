@php
$addInvoicesPermission = user()->permission('add_invoices');
@endphp

<!-- ROW START -->
<div class="row pb-5">
    <div class="col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4">
        <!-- Add Task Export Buttons Start -->
        <div class="d-flex" id="table-actions">
            @if ($addInvoicesPermission == 'all' || $addInvoicesPermission == 'added')
                <x-forms.link-primary :link="route('invoices.create').'?client_id='.$client->id"
                    class="mr-3 float-left openRightModal"
                    data-redirect-url="{{ route('clients.show', $client->id) . '?tab=invoices' }}" icon="plus">
                    @lang('modules.invoices.addInvoice')
                </x-forms.link-primary>
            @endif
        </div>
        <!-- Add Task Export Buttons End -->

        <form action="" id="filter-form">
            <div class="d-block d-lg-flex d-md-flex my-3">
                <!-- STATUS START -->
                <div class="select-box py-2 px-0 mr-3">
                    <x-forms.label :fieldLabel="__('app.status')" fieldId="status" />
                    <select class="form-control select-picker" name="status" id="status" data-live-search="true"
                        data-size="8">
                        <option value="all">@lang('app.all')</option>
                        <option value="unpaid">@lang('app.unpaid')</option>
                        <option value="paid">@lang('app.paid')</option>
                        <option value="partial">@lang('app.partial')</option>
                        <option value="canceled">@lang('app.canceled')</option>
                    </select>
                </div>
                <!-- STATUS END -->

                <!-- SEARCH BY TASK START -->
                <div class="select-box py-2 px-lg-2 px-md-2 px-0 mr-3">
                    <x-forms.label fieldId="status" />
                    <div class="input-group bg-grey rounded">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-additional-grey">
                                <i class="fa fa-search f-13 text-dark-grey"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control f-14 p-1 height-35 border" id="search-text-field"
                            placeholder="@lang('app.startTyping')">
                    </div>
                </div>
                <!-- SEARCH BY TASK END -->

                <!-- RESET START -->
                <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 mt-4">
                    <x-forms.button-secondary class="btn-xs d-none height-35" id="reset-filters"
                        icon="times-circle">
                        @lang('app.clearFilters')
                    </x-forms.button-secondary>
                </div>
                <!-- RESET END -->
            </div>
        </form>

        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
</div>

@include('sections.datatable_js')

<script>
    $('#invoices-table').on('preXhr.dt', function(e, settings, data) {

        var clientID = "{{ $client->id }}";
        var status = $('#status').val();
        var searchText = $('#search-text-field').val();

        data['status'] = status;
        data['searchText'] = searchText;
        data['clientID'] = clientID;
    });
    const showTable = () => {
        window.LaravelDataTables["invoices-table"].draw(false);
    }

    $('#quick-action-type').change(function() {
        const actionValue = $(this).val();
        if (actionValue != '') {
            $('#quick-action-apply').removeAttr('disabled');

            if (actionValue == 'change-status') {
                $('.quick-action-field').addClass('d-none');
                $('#change-status-action').removeClass('d-none');
            } else {
                $('.quick-action-field').addClass('d-none');
            }
        } else {
            $('#quick-action-apply').attr('disabled', true);
            $('.quick-action-field').addClass('d-none');
        }
    });

    $('#quick-action-apply').click(function() {
        const actionValue = $('#quick-action-type').val();
        if (actionValue == 'delete') {
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    applyQuickAction();
                }
            });

        } else {
            applyQuickAction();
        }
    });

    $('body').on('click', '.delete-table-row', function() {
        var id = $(this).data('invoice-id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                var url = "{{ route('invoices.destroy', ':id') }}";
                url = url.replace(':id', id);

                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    blockUI: true,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                        }
                    }
                });
            }
        });
    });

    const applyQuickAction = () => {
        var rowdIds = $("#invoices-table input:checkbox:checked").map(function() {
            return $(this).val();
        }).get();

        var url = "{{ route('invoices.apply_quick_action') }}?row_ids=" + rowdIds;

        $.easyAjax({
            url: url,
            container: '#quick-action-form',
            type: "POST",
            disableButton: true,
            buttonSelector: "#quick-action-apply",
            data: $('#quick-action-form').serialize(),
            blockUI: true,
            success: function(response) {
                if (response.status == 'success') {
                    showTable();
                    resetActionButtons();
                }
            }
        })
    };

    $('body').on('click', '.sendButton', function() {
        var id = $(this).data('invoice-id');
        var url = "{{ route('invoices.send_invoice', ':id') }}";
        url = url.replace(':id', id);

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            type: 'POST',
            url: url,
            container: '#invoices-table',
            blockUI: true,
            data: {
                '_token': token
            },
            success: function(response) {
                if (response.status == "success") {
                    window.LaravelDataTables["invoices-table"].draw(false);
                }
            }
        });
    });

    $('body').on('click', '.reminderButton', function() {
        var id = $(this).data('invoice-id');
        var url = "{{ route('invoices.payment_reminder', ':id') }}";
        url = url.replace(':id', id);

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            type: 'GET',
            container: '#invoices-table',
            blockUI: true,
            url: url,
            success: function(response) {
                if (response.status == "success") {
                    $.unblockUI();
                    window.LaravelDataTables["invoices-table"].draw(false);
                }
            }
        });
    });

    $('body').on('click', '.invoice-upload', function() {
        var invoiceId = $(this).data('invoice-id');
        const url = "{{ route('invoices.file_upload') }}?invoice_id=" + invoiceId;
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.unpaidAndPartialPaidCreditNote', function() {
        var id = $(this).data('invoice-id');

        Swal.fire({
            title: "@lang('messages.confirmation.createCreditNotes')",
            text: "@lang('messages.creditText')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('app.yes')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                var url = "{{ route('creditnotes.create') }}?invoice=:id";
                url = url.replace(':id', id);

                location.href = url;
            }
        });
    });

    $('body').on('click', '.cancel-invoice', function() {
        var id = $(this).data('invoice-id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.invoiceText')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('app.yes')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {

                var url = "{{ route('invoices.update_status', ':id') }}";
                url = url.replace(':id', id);

                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'GET',
                    url: url,
                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                        }
                    }
                });
            }
        });
    });

    $('#search-text-field, #status')
        .on('change keyup',
            function() {
                if ($('#status').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#search-text-field').val() != "") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else {
                    $('#reset-filters').addClass('d-none');
                    showTable();
                }
            });

    $('#reset-filters').click(function() {
        $('#filter-form')[0].reset();

        $('.filter-box .select-picker').selectpicker("refresh");
        $('#reset-filters').addClass('d-none');
        showTable();
    });

    $('body').on('click', '.add-shipping-address', function() {
        let invoiceId = $(this).data('invoice-id');

        var url = "{{ route('invoices.shipping_address_modal', [':id']) }}";
        url = url.replace(':id', invoiceId);

        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });


</script>
