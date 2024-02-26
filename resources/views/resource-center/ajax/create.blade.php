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
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.emailMarketing.title')">
                                </x-forms.text>
                                <x-forms.text fieldId="url" :fieldLabel="__('modules.resourceCenter.url')" fieldName="url"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.resourceCenter.url')">
                                </x-forms.text>
                                <input type="hidden" name="icon" value="" id="icon">
                                <x-forms.text fieldId="iconhtml" :fieldLabel="__('modules.resourceCenter.icon')" fieldName="iconhtml"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.resourceCenter.icon')">
                                </x-forms.text>
                                <!-- <x-forms.text fieldId="colour" :fieldLabel="__('modules.resourceCenter.colour')" fieldName="colour"
                                              fieldRequired="false" :fieldPlaceholder="__('placeholders.resourceCenter.colour')">
                                </x-forms.text> -->
                            </div>
                        </div>
                    </div>
                    <input type ="hidden" name="add_more" value="false" id="add_more" />
                </div>

                <x-form-actions>
                    <x-forms.button-primary id="save-resource-center" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-secondary class="mr-3" id="save-more-resource-center" icon="check-double">@lang('app.saveAddMore')
                    </x-forms.button-secondary>
                    <x-forms.button-cancel :link="route('resource-center.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>
<script>
    $(document).ready(function () {
        
        $('#save-more-resource-center').click(function () {
            const iconHtml = $('#iconhtml').val();
            $('#icon').val(encodeURIComponent(iconHtml));

            $('#add_more').val(true);

            const url = "{{ route('resource-center.store') }}";
            var data = $('#save-resource-center-form').serialize();

            saveEmailTemplate(data, url, "#save-more-resource-center");

        });


        $('#save-resource-center').click(function() {

            const iconHtml = $('#iconhtml').val();
            $('#icon').val(encodeURIComponent(iconHtml));

            const url = "{{ route('resource-center.store') }}";
            var data = $('#save-resource-center-form').serialize();
            console.log(data);

            saveEmailTemplate(data, url, "#save-resource-center");

        });

        function saveEmailTemplate(data, url, buttonSelector) {

            $.easyAjax({
                url: url,
                container: '#save-resource-center-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: buttonSelector,
                file: false,
                data: data,
                success: function(response) {
                    if(response.add_more == true) {

                     var right_modal_content = $.trim($(RIGHT_MODAL_CONTENT).html());

                        if(right_modal_content.length) {

                            $(RIGHT_MODAL_CONTENT).html(response.html.html);
                            $('#add_more').val(false);
                        }
                        else {

                            $('.content-wrapper').html(response.html.html);
                            init('.content-wrapper');
                            $('#add_more').val(false);
                        }
                    }

                    else{
                        if (response.redirectUrl == 'no') {
                            getProductOptions();
                            closeTaskDetail();
                        } else if ($(MODAL_XL).hasClass('show')) {
                            $(MODAL_XL).modal('hide');
                            window.location.reload();
                        } else {
                            window.location.href = response.redirectUrl;
                        }
                    }

                    if (typeof showTable !== 'undefined' && typeof showTable === 'function') {
                        showTable();
                    }

                }
            });
        }
    });
</script>
