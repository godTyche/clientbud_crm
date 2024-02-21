<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('app.leavesApproveReason')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <div class="portlet-body">
        <x-form id="followUpForm" method="POST" class="ajax-form">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <x-forms.textarea class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.reason')"
                                fieldName="approve_reason" fieldId="approve_reason">
                            </x-forms.textarea>
                        </div>
                    </div>
                </div>
            </div>
        </x-form>
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    <x-forms.button-primary id="save-approve-leave" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    // save leave
    $('#save-approve-leave').click(function() {
        let url = '{{ route("leaves.leave_action") }}';
        let action = '{{ $leaveAction }}';
        let leaveId = '{{ $leaveID }}';
        let type = '{{$type}}';
        let userId = $('.leave-action-approved').data('user-id');
        let approve_reason = $('#approve_reason').val();

        $.easyAjax({
            url: url,
            type: "POST",
            blockUI: true,
            data: {
                'action': action,
                'leaveId': leaveId,
                '_token': '{{ csrf_token() }}',
                'approveReason': approve_reason,
                'userId': userId,
                'type' : type
            },
            success: function(response) {
                if (response.status == "success") {
                    window.location.reload();
                }
            }
        })
    });
</script>
