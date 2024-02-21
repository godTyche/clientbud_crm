@php
$editLeavePermission = user()->permission('edit_leave');
$deleteLeavePermission = user()->permission('delete_leave');
$approveRejectPermission = user()->permission('approve_or_reject_leaves');
@endphp

<div class="row">
    <div class="col-sm-12">
        <div class="card bg-white border-0 b-shadow-4">
            <div class="card-header bg-white  border-bottom-grey text-capitalize justify-content-between p-20">
                <div class="row">
                    <div class="col-lg-8 col-xs-4">
                        <h3 class="heading-h1 mb-3">@lang('app.multipleDetails')</h3>
                    </div>
                    <div class="col-lg-4 col-xs-8 text-right">
                        @php
                            if($pendingCountLeave != count($multipleLeaves)){
                                $approveTitle = __('modules.leaves.approveRemaining');
                                $rejectTitle = __('modules.leaves.rejectRemaining');
                            }
                            else {
                                $approveTitle = __('modules.leaves.approveAll');
                                $rejectTitle = __('modules.leaves.rejectAll');
                            }
                        @endphp

                        @if ($pendingCountLeave > 0 && $approveRejectPermission == 'all')
                            <a class="btn btn-secondary rounded f-14 p-2 leave-action-approved" data-leave-id="{{ $multipleLeaves->first()->unique_id }}"
                                data-leave-action="approved" data-type="approveAll" class="mr-3" icon="check" href="javascript:;">
                                <i class="fa fa-check mr-2"></i>{{$approveTitle}}</a>

                            <a class="btn btn-secondary rounded f-14 p-2 leave-action-reject" data-leave-id="{{ $multipleLeaves->first()->unique_id }}"
                                data-leave-action="rejected" data-type="rejectAll" class="mr-3" icon="check" href="javascript:;">
                                <i class="fa fa-times mr-2"></i>{{$rejectTitle}}</a>
                        @endif

                    </div>
                </div>
            </div>
            <div class="card-body">
                @include('leaves.multiple-leave-table')
            </div>
        </div>
    </div>
</div>

<script>
    $('body').on('click', '.leave-action-approved', function() {
        let action = $(this).data('leave-action');
        let leaveId = $(this).data('leave-id');
        var type = $(this).data('type');
            if(type == undefined){
                var type = 'single';
            }
        let searchQuery = "?leave_action=" + action + "&leave_id=" + leaveId + "&type=" + type;
        let url = "{{ route('leaves.show_approved_modal') }}" + searchQuery;

        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.leave-action-reject', function() {
        let action = $(this).data('leave-action');
        let leaveId = $(this).data('leave-id');
        var type = $(this).data('type');
            if(type == undefined){
                var type = 'single';
            }
        let searchQuery = "?leave_action=" + action + "&leave_id=" + leaveId + "&type=" + type;
        let url = "{{ route('leaves.show_reject_modal') }}" + searchQuery;

        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.delete-multiple-leave', function() {
        var type = $(this).data('type');
        var id = $(this).data('leave-id');
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
                var url = "{{ route('leaves.destroy', ':id') }}";
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
                        if(response.status == "success"){
                            if(response.redirectUrl == undefined){
                                window.location.reload();
                            } else{
                                window.location.href = response.redirectUrl;
                            }
                        }
                    }
                });
            }
        });
    });
</script>
