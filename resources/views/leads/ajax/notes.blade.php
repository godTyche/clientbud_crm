@php
$addDealNotePermission = user()->permission('add_deal_note');
@endphp

<!-- ROW START -->
<div class="row pb-5">
    <div class="col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4">
        <!-- Add Task Export Buttons Start -->
        <div class="d-flex justify-content-between action-bar">
            <div id="table-actions" class="d-flex align-items-center">
                @if ($addDealNotePermission == 'all' || $addDealNotePermission == 'added' || $addDealNotePermission == 'both')
                    <x-forms.link-primary :link="route('deal-notes.create').'?lead='.$deal->id"
                        class="mr-3 openRightModal" icon="plus">
                        @lang('modules.client.createNote')
                    </x-forms.link-primary>
                @endif
            </div>
            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                        <option value="">@lang('app.selectAction')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
            </x-datatable.actions>
        </div>
        <!-- Add Task Export Buttons End -->

        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
</div>

@include('sections.datatable_js')

<script>
    $('#deal-notes-table').on('preXhr.dt', function(e, settings, data) {
        var leadID = "{{ $deal->id }}";
        data['leadID'] = leadID;
    });

    const showTable = () => {
        window.LaravelDataTables["deal-notes-table"].draw(false);
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

    $('body').on('click', '.delete-table-row-lead', function() {
        var id = $(this).data('id');
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
                var url = "{{ route('deal-notes.destroy', ':id') }}";
                url = url.replace(':id', id);
                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
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
        var rowdIds = $("#deal-notes-table input:checkbox:checked").map(function() {
            return $(this).val();
        }).get();

        var url = "{{ route('deal-notes.apply_quick_action') }}?row_ids=" + rowdIds;

        $.easyAjax({
            url: url,
            container: '#quick-action-form',
            type: "POST",
            disableButton: true,
            buttonSelector: "#quick-action-apply",
            data: $('#quick-action-form').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    showTable();
                    resetActionButtons();
                    deSelectAll();
                }
            }
        })
    };

    // show note detail in right modal
    var getNoteDetail = function(id) {
        openTaskDetail();

        var url = "{{ route('deal-notes.show', ':id') }}";
        url = url.replace(':id', id);

        $.easyAjax({
            url: url,
            blockUI: true,
            container: RIGHT_MODAL,
            historyPush: true,
            success: function(response) {
                if (response.status == "success") {
                    $(RIGHT_MODAL_CONTENT).html(response.html);
                    $(RIGHT_MODAL_TITLE).html(response.title);
                }
            },
            error: function(request, status, error) {
                if (request.status == 403) {
                    $(RIGHT_MODAL_CONTENT).html(
                        '<div class="align-content-between d-flex justify-content-center mt-105 f-21">403 | Permission Denied</div>'
                    );
                } else if (request.status == 404) {
                    $(RIGHT_MODAL_CONTENT).html(
                        '<div class="align-content-between d-flex justify-content-center mt-105 f-21">404 | Not Found</div>'
                    );
                } else if (request.status == 500) {
                    $(RIGHT_MODAL_CONTENT).html(
                        '<div class="align-content-between d-flex justify-content-center mt-105 f-21">500 | Something Went Wrong</div>'
                    );
                }
            }
        });
    };

</script>
