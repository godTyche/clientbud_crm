<x-form id="addLeadSource" method="POST" class="ajax-form">
    <div class="modal-header">
        <h5 class="modal-title" id="modelHeading">@lang('modules.lead.addLeadSource')</h5>
        <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
                aria-hidden="true">×</span></button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
                <div class="form-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <x-forms.text fieldId="type" :fieldLabel="__('modules.lead.leadSource')"
                                fieldName="type" fieldRequired="true">
                            </x-forms.text>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
        <x-forms.button-primary id="save-source" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>

<script>
    // save source
    $('#save-source').click(function() {
        $.easyAjax({
            url: "{{ route('lead-source-settings.store') }}",
            container: '#addLeadSource',
            type: "POST",
            blockUI: true,
            disableButton: true,
            buttonSelector: "#save-source",
            data: $('#addLeadSource').serialize(),
            success: function(response) {
                if (response.status == "success") {
                    if($('table#example').length) {
                        window.location.reload();
                    }
                    else {
                        $('#source_id').html(response.data);
                        $('#source_id').selectpicker('refresh');
                        // $('#source_id').html(options);
                        // $('#source_id').selectpicker('refresh');
                        $(MODAL_LG).modal('hide');
                    }
                }
            }
        })
    });
</script>
