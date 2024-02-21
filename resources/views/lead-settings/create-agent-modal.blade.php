<x-form id="createAgent" method="POST" class="form-horizontal">
    <div class="modal-header">
        <h5 class="modal-title">@lang('app.addNewLeadAgent')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="my-3">
                            <x-forms.select fieldId="agent_name" :fieldLabel="__('modules.tickets.chooseAgents')"
                                            fieldName="agent_name[]" search="true" multiple="true" fieldRequired="true">
                                @foreach ($employees as $emp)
                                    <x-user-option :user="$emp" :pill="true"/>
                                @endforeach
                            </x-forms.select>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-agent" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>

<script>
    $("#agent_name").selectpicker({
        actionsBox: true,
        selectAllText: "{{ __('modules.permission.selectAll') }}",
        deselectAllText: "{{ __('modules.permission.deselectAll') }}",
        multipleSeparator: " ",
        selectedTextFormat: "count > 8",
        countSelectedText: function (selected, total) {
            return selected + " {{ __('app.membersSelected') }} ";
        }
    });

    // save agent
    $('#save-agent').click(function () {

        $.easyAjax({
            url: "{{ route('lead-agent-settings.store') }}",
            container: '#createAgent',
            type: "POST",
            blockUI: true,
            data: $('#createAgent').serialize(),
            disableButton: true,
            buttonSelector: "#save-agent",
            success: function (response) {
                if (response.status == "success") {
                    if ($('table#example').length) {
                        window.location.reload();
                    } else {
                        $('#agent_id').html(response.data);
                        $('#agent_id').selectpicker('refresh');
                        $(MODAL_LG).modal('hide');
                    }
                }
            }
        })
    });

</script>
