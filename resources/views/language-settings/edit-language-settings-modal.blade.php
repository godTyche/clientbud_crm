<div class="modal-header">
    <h5 class="modal-title">@lang('app.editLanguage')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<div class="modal-body">
    <div class="portlet-body">
        <x-form id="editLanguage" method="POST" class="form-horizontal">
            <div class="row">
                <div class="col-lg-6">
                    <x-forms.text :fieldLabel="__('app.name')" :fieldPlaceholder="__('placeholders.language.languageName')" fieldName="language_name" fieldId="language_name" :fieldValue="$languageSetting->language_name" fieldRequired="true"/>
                </div>

                <div class="col-lg-6">
                    <x-forms.text :fieldLabel="__('app.language_code')" :fieldPlaceholder="__('placeholders.language.languageCode')" fieldName="language_code" fieldId="language_code" :fieldValue="$languageSetting->language_code" fieldRequired="true"/>
                </div>

                <div class="col-lg-6">
                    <x-forms.select fieldId="flag" :fieldLabel="__('modules.flag')" fieldName="flag" search="true" fieldRequired="true">
                        <option value="">--</option>

                        @foreach ($flags as $flag)
                            <option data-content="<span class='flag-icon flag-icon-{{ $flag->code }} flag-icon-squared'></span> {{ $flag->name }}"
                            value="{{ $flag->code }}" @if($languageSetting->flag_code == $flag->code) selected @endif>{{ $flag->name }}</option>
                        @endforeach
                    </x-forms.select>
                </div>

                <div class="col-lg-6">
                    <x-forms.select fieldId="status" :fieldLabel="__('app.status')" fieldName="status" search="true">
                        <option value="enabled" @if($languageSetting->status == 'enabled') selected @endif>@lang('app.enabled')</option>
                        <option value="disabled" @if($languageSetting->status == 'disabled') selected @endif>@lang('app.disabled')</option>
                    </x-forms.select>
                </div>
            </div>
        </x-form>
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-language" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>

    $(".select-picker").selectpicker();

    $('#save-language').click(function () {
        $.easyAjax({
            container: '#editLanguage',
            type: "POST",
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-language",
            url: "{{route('language_settings.update_data', $languageSetting->id)}}",
            data: $('#editLanguage').serialize(),
            success: function (response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        })
    });

</script>

