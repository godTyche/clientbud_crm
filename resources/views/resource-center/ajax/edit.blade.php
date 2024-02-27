@php
    $addProductCategoryPermission = user()->permission('manage_product_category');
    $addProductSubCategoryPermission = user()->permission('manage_product_sub_category');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-resource-center-form">

            <a href="https://fontawesomelib.com/releases/5.3.1/list/all/index.html" class="btn btn-info mt-2 mr-2" style="float:right" target="_blank">Icon Library</a>
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('modules.resourceCenter.addResource')</h4>
                <div class="row p-20">
                    <div class="col-lg-12">
                        <div class="row">

                            <input type="hidden" id="hiddenEmailId">

                            <div class="col-lg-12 col-md-12">
                                <x-forms.text fieldId="title" :fieldLabel="__('app.title')" fieldName="title"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.emailMarketing.title')" :fieldValue="$resourceCenter->title">
                                </x-forms.text>
                                <x-forms.text fieldId="url" :fieldLabel="__('modules.resourceCenter.url')" fieldName="url"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.resourceCenter.url')" :fieldValue="$resourceCenter->url">
                                </x-forms.text>
                                <input type="hidden" name="icon" value="" id="icon">
                                <x-forms.text fieldId="iconhtml" :fieldLabel="__('modules.resourceCenter.icon')" fieldName="iconhtml"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.resourceCenter.icon')" :fieldValue="urldecode($resourceCenter->icon)">
                                </x-forms.text>
                                <x-forms.label fieldId="selectStaffAssignee" fieldRequired="true"
                                    :fieldLabel="__('app.assign').' '.__('app.employee')">
                                </x-forms.label>
                                <?php 
                                    $employeesAssignee = explode(',', $resourceCenter->employees);
                                    $clientsAssignee = explode(',', $resourceCenter->clients);
                                ?>
                                <x-forms.input-group>
                                    <select class="form-control multiple-users" multiple name="employee_id[]"
                                        id="selectStaffAssignee2" data-live-search="true" data-size="8">
                                        @foreach ($employees as $item)
                                            <x-user-option :user="$item" :pill="true" :selected="in_array($item->id, $employeesAssignee)"/>
                                        @endforeach
                                    </select>
                                </x-forms.input-group>
                                <x-forms.label fieldId="selectClientAssignee" fieldRequired="true"
                                    :fieldLabel="__('app.assign').' '.__('app.client')" class="mt-3">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control multiple-users" multiple name="client_id[]"
                                        id="selectClientAssignee2" data-live-search="true" data-size="8">
                                        @foreach ($clients as $item)
                                            <x-user-option :user="$item" :pill="true" :selected="in_array($item->id, $clientsAssignee)"/>
                                        @endforeach
                                    </select>
                                </x-forms.input-group>
                                <!-- <x-forms.text fieldId="colour" :fieldLabel="__('modules.resourceCenter.colour')" fieldName="colour"
                                              fieldRequired="false" :fieldPlaceholder="__('placeholders.resourceCenter.colour')" :fieldValue="$resourceCenter->colour">
                                </x-forms.text> -->
                            </div>
                        </div>
                    </div>
                    <input type ="hidden" name="add_more" value="false" id="add_more" />
                </div>

                <x-form-actions>
                    <x-forms.button-primary id="save-resource-center" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('resource-center.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>
<script>
    $(document).ready(function () {

        $("#selectClientAssignee, #selectClientAssignee2, #selectStaffAssignee, #selectStaffAssignee2").selectpicker({
            actionsBox: true,
            selectAllText: "{{ __('modules.permission.selectAll') }}",
            deselectAllText: "{{ __('modules.permission.deselectAll') }}",
            multipleSeparator: " ",
            selectedTextFormat: "count > 8",
            countSelectedText: function(selected, total) {
                return selected + " {{ __('app.membersSelected') }} ";
            }
        });

        $('#save-resource-center').click(function() {
            const iconHtml = $('#iconhtml').val();
            $('#icon').val(encodeURIComponent(iconHtml));

            const url = "{{ route('resource-center.update', [$resourceCenter->id]) }}";
            var data = $('#save-resource-center-form').serialize();

            $.easyAjax({
                url: url,
                container: '#save-resource-center-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-resource-center",
                file: true,
                data: data,
                success: function(response) {
                    if ($(MODAL_XL).hasClass('show')) {
                        $(MODAL_XL).modal('hide');
                        window.location.reload();
                    } else {
                        window.location.href = response.redirectUrl;
                    }
                }
            });

        });
    });
</script>
