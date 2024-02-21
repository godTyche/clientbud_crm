<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('app.menu.addTemplate')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span>
    </button>
</div>

<div class="modal-body">
    <div class="portlet-body">
        <x-form id="addTicketTemplate" method="POST" class="ajax-form">
            <div class="form-body">
                <div class="row">
                    <div class="col-lg-12">
                        <x-forms.text fieldId="reply_heading" :fieldLabel="__('modules.tickets.templateHeading')"
                            fieldName="reply_heading" fieldRequired="true" :fieldPlaceholder="__('placeholders.ticket.replyTicket')">
                        </x-forms.text>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <x-forms.label fieldId="description" fieldRequired="true" :fieldLabel="__('modules.tickets.templateText')">
                            </x-forms.label>
                            <div id="description"></div>
                            <textarea name="description" id="description-text" class="d-none"></textarea>
                        </div>
                    </div>

                </div>
            </div>
        </x-form>
    </div>
</div>

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    <x-forms.button-primary id="save-template" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $(document).ready(function() {
        quillImageLoad('#description');
    });

    // Save template
    $('#save-template').click(function() {
        var note = document.getElementById('description').children[0].innerHTML;
        document.getElementById('description-text').value = note;

        $.easyAjax({
            url: "{{ route('replyTemplates.store') }}",
            container: '#addTicketTemplate',
            type: "POST",
            blockUI: true,
            data: $('#addTicketTemplate').serialize(),
            success: function(response) {
                if (response.status == "success") {
                    window.location.reload();
                }
            }
        })
    });

</script>
