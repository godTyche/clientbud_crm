<div class="row">
    <div class="col-sm-12">
        <x-form id="save-client-note-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('app.clientNoteDetails')</h4>

                <input type="hidden" name="client_id" value="{{ $clientId }}">

                <div class="row px-4">

                    <div class="col-md-6">
                        <x-forms.text fieldId="title" :fieldLabel="__('modules.client.noteTitle')" fieldName="title"
                            fieldRequired="true" :fieldPlaceholder="__('placeholders.note')">
                        </x-forms.text>
                    </div>

                    <div class="col-md-6 col-lg-6">
                        <div class="form-group my-3">
                            <x-forms.label fieldId="late_yes" :fieldLabel="__('modules.client.noteType')">
                            </x-forms.label>
                            <div class="d-flex ">
                                <x-forms.radio fieldId="public" :fieldLabel="__('app.public')" fieldName="type"
                                    fieldValue="0" checked="true">
                                </x-forms.radio>
                                <x-forms.radio fieldId="private" :fieldLabel="__('app.private')" fieldValue="1"
                                    fieldName="type"></x-forms.radio>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row px-4 d-none" id="private-note-details">

                    <div class="col-md-6">
                        <div class="form-group">
                            <x-forms.label class="my-3" fieldId="selectEmployee" :fieldRequired="(!in_array('client', user_roles())) ? true : false" :fieldLabel="__('app.employee')">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control multiple-users" multiple name="user_id[]"
                                    id="selectEmployee" data-live-search="true" data-size="8">
                                    @foreach ($employees as $item)
                                        <x-user-option :user="$item" :pill="true" />
                                    @endforeach
                                </select>
                            </x-forms.input-group>
                        </div>
                    </div>
                    @if(!in_array('client', user_roles()))
                        <div class="col-mb-3">
                            <x-forms.label class="my-3" fieldId="selectEmployee" fieldLabel="&nbsp;" />
                            <x-forms.checkbox :fieldLabel="__('modules.client.visibleToClient')" fieldName="is_client_show"
                                fieldId="is_client_show" fieldValue="1" fieldRequired="true" />
                        </div>
                    @endif
                    <div class="col-lg-3">
                        <x-forms.label class="my-3" fieldId="selectEmployee" fieldLabel="&nbsp;" />
                        <x-forms.checkbox :fieldLabel="__('modules.client.askToReenterPassword')"
                            fieldName="ask_password" fieldId="ask_password" fieldValue="1" fieldRequired="true" />
                    </div>

                </div>

                <div class="row px-4">
                    <div class="col-md-12 col-lg-12">
                        <div class="form-group">
                            <x-forms.label class="my-3" fieldId="notes" :fieldLabel="__('modules.client.noteDetail')">
                            </x-forms.label>
                            <div id="details"></div>
                            <textarea name="details" id="details-text" class="d-none"></textarea>
                        </div>
                    </div>
                </div>

                <x-form-actions>
                    <x-forms.button-primary id="save-client-note-form" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="in_array('client', user_roles()) ? route('client-notes.index') : route('clients.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>

            </div>

        </x-form>

    </div>
</div>

<script>
    $(document).ready(function() {

        $("#selectEmployee").selectpicker({
            actionsBox: true,
            selectAllText: "{{ __('modules.permission.selectAll') }}",
            deselectAllText: "{{ __('modules.permission.deselectAll') }}",
            multipleSeparator: " ",
            selectedTextFormat: "count > 8",
            countSelectedText: function(selected, total) {
                return selected + " {{ __('app.membersSelected') }} ";
            }
        });

        quillImageLoad('#details');

        $('#save-client-note-form').click(function() {
            var comment = document.getElementById('details').children[0].innerHTML;
            document.getElementById('details-text').value = comment;


            const url = "{{ route('client-notes.store') }}";

            $.easyAjax({
                url: url,
                container: '#save-client-note-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-client-note-form",
                data: $('#save-client-note-data-form').serialize(),
                success: function(response) {

                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            })
        });

        $("input[name=type]").click(function() {
            $(this).val() == 1 ? $('#private-note-details').removeClass('d-none') : $(
                '#private-note-details').addClass('d-none');
        })

        init(RIGHT_MODAL);
    });
</script>
