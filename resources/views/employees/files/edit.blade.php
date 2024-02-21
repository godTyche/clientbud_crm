<x-form id="update-employee-file-data-form">
    <div class="modal-header">
        <h5 class="modal-title" id="modelHeading">@lang('app.editFile')</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                aria-hidden="true">×</span></button>
    </div>
    <div class="modal-body">


        @method('PUT')
        <div class="row">
            <div class="col-md-12">
                <x-forms.text :fieldLabel="__('modules.projects.fileName')" fieldName="name"
                              fieldRequired="true" fieldId="name" :fieldValue="$file->name"/>
            </div>
            <div class="col-md-12">
                <x-forms.file :fieldLabel="__('modules.projects.uploadFile')" fieldName="file"
                              fieldRequired="true" fieldId="file"
                              allowedFileExtensions="txt pdf doc xls xlsx docx rtf png jpg jpeg svg"
                              :popover="__('messages.fileFormat.multipleImageFile')" :fieldValue="$file->doc_url"/>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="submit-document" icon="check">@lang('app.submit')
        </x-forms.button-primary>
    </div>
</x-form>
<script>

    $(document).ready(function () {
        init('#update-employee-file-data-form');

        $('body').on('click', '#submit-document', function () {
            var url = "{{ route('employee-docs.update', $file->id) }}";

            $.easyAjax({
                url: url,
                container: '#update-employee-file-data-form',
                type: "POST",
                file: true,
                blockUI: true,
                data: $('#update-employee-file-data-form').serialize(),
                success: function (response) {
                    if (response.status == 'success') {
                        window.location.reload();
                    }
                }
            })
        });
    });


</script>
