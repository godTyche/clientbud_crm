@php
$viewLeadAgentPermission = user()->permission('view_lead_agents');
$viewLeadCategoryPermission = user()->permission('view_lead_category');
$viewLeadSourcesPermission = user()->permission('view_lead_sources');
$addLeadAgentPermission = user()->permission('add_lead_agent');
$addLeadSourcesPermission = user()->permission('add_lead_sources');
$addLeadCategoryPermission = user()->permission('add_lead_category');
$addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-lead-data-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('modules.deal.dealDetails')</h4>

                <div class="row p-20">
                    {{-- <div class="col-lg-4 ">
                        <x-forms.select fieldId="lead_contact" :fieldLabel="__('modules.leadContact.leadContacts')" fieldName="lead_contact" fieldRequired="true">
                            <option value="">--</option>
                            @foreach ($leadContacts as $leadContact)
                                <option @if($leadContact->id == $deal->lead_id) selected @endif value="{{ $leadContact->id }}">
                                    {{ $leadContact->client_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div> --}}


                    <div class="col-lg-4 col-md-6">
                        <x-forms.text :fieldLabel="__('app.name')" fieldName="name"
                            fieldId="name" fieldPlaceholder="" fieldRequired="true"
                            :fieldValue="$deal->name" />
                    </div>

                    <div class="col-lg-4">
                        <x-forms.select fieldId="editPipeline" :fieldLabel="__('modules.deal.pipeline')" fieldName="pipeline" fieldRequired="true">
                            @foreach ($leadPipelines as $pipeline)
                                <option @if($pipeline->id == $deal->lead_pipeline_id) selected @endif value="{{ $pipeline->id }}">
                                    {{ $pipeline->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>
                    <div class="col-lg-4">
                        <x-forms.select fieldId="stages" :fieldLabel="__('modules.deal.stages')" fieldName="stage_id" fieldRequired="true">
                            @foreach ($stages as $stage)
                                <option @if($stage->id == $deal->pipeline_stage_id) selected @endif value="{{ $stage->id }}">
                                    {{ $stage->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    @if ($viewLeadAgentPermission != 'none')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.label class="my-3" fieldId="agent_id" :fieldLabel="__('modules.deal.dealAgent')">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="agent_id" id="agent_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($leadAgents as $emp)
                                        <x-user-option :user="$emp->user" :selected="$emp->id == $deal->agent_id" :userID="$emp->id" />
                                    @endforeach
                                </select>

                                @if ($addLeadAgentPermission == 'all' || $addLeadAgentPermission == 'added')
                                    <x-slot name="append">
                                        <button type="button"
                                            class="btn btn-outline-secondary border-grey add-lead-agent"
                                            data-toggle="tooltip" data-original-title="{{ __('app.add').'  '.__('app.new').' '.__('modules.tickets.agents') }}">@lang('app.add')</button>
                                    </x-slot>
                                @endif
                            </x-forms.input-group>
                        </div>
                    @endif

                    <div class="col-lg-4 col-md-6 mt-4">
                        <x-forms.label fieldId="value" :fieldLabel="__('modules.deal.dealValue')">
                        </x-forms.label>
                        <x-forms.input-group>
                            <x-slot name="prepend">
                                <span
                                    class="input-group-text f-14">{{!is_null($deal->currency_id) ? $deal->currency->currency_code : company()->currency->currency_code}} ( {{ !is_null($deal->currency_id) ? $deal->currency->currency_symbol : company()->currency->currency_symbol }} )</span>
                            </x-slot>
                            <input type="number" name="value" id="value" class="form-control height-35 f-14" value="{{$deal->value}}"/>
                        </x-forms.input-group>
                    </div>
                    <div class="col-md-5 col-lg-3 dueDateBox mt-1">
                        <x-forms.datepicker fieldId="close_date" class="custom-date-picker" fieldRequired="true" :fieldLabel="__('modules.deal.closeDate')"
                                fieldName="close_date" :fieldPlaceholder="__('placeholders.date')"
                                :fieldValue="(($deal->close_date) ? $deal->close_date->format(company()->date_format) : '')"/>
                    </div>

                    @if(in_array('products', user_modules()) || in_array('purchase', user_modules()))
                        <div class="col-lg-4 mt-3">
                            <div class="form-group">
                                <x-forms.label fieldId="selectProduct" :fieldLabel="__('app.menu.products')" >
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" data-live-search="true" data-size="8"  name="product_id[]" multiple  id="add-products" title="{{ __('app.menu.selectProduct') }}">
                                        @foreach ($products as $item)
                                            <option @if(in_array($item->id, $productIds)) selected @endif data-content="{{ $item->name }}" value="{{ $item->id }}">
                                                {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    @if ($addProductPermission == 'all' || $addProductPermission == 'added')
                                        <x-slot name="append">
                                            <a href="{{ route('products.create') }}" data-redirect-url="no"
                                                class="btn btn-outline-secondary border-grey openRightModal"
                                                data-toggle="tooltip" data-original-title="{{ __('app.add').' '.__('modules.dashboard.newproduct') }}">@lang('app.add')</a>
                                        </x-slot>
                                    @endif
                                </x-forms.input-group>
                            </div>
                        </div>
                    @endif

                </div>

                <x-forms.custom-field :fields="$fields" :model="$deal"></x-forms.custom-field>

                <x-form-actions>
                    <x-forms.button-primary id="save-lead-form" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('tasks.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>

            </div>
        </x-form>

    </div>
</div>


<script>
    $(document).ready(function() {

        $('#close_date').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        $('#save-lead-form').click(function() {
            const url = "{{ route('deals.update', [$deal->id]) }}";
            $.easyAjax({
                url: url,
                container: '#save-lead-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                file: true,
                buttonSelector: "#save-lead-form",
                data: $('#save-lead-data-form').serialize(),
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });

        $('body').on('click', '.add-lead-agent', function() {
            const url = '{{ route('lead-agent-settings.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.add-lead-source', function() {
            const url = '{{ route('lead-source-settings.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.add-lead-category', function() {
            var url = '{{ route('leadCategory.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#create_task_category').click(function() {
            const url = "{{ route('taskCategory.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#department-setting').click(function() {
            const url = "{{ route('departments.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#client_view_task').change(function() {
            $('#clientNotification').toggleClass('d-none');
        });

        $('#set_time_estimate').change(function() {
            $('#set-time-estimate-fields').toggleClass('d-none');
        });

        $('#repeat-task').change(function() {
            $('#repeat-fields').toggleClass('d-none');
        });

        $('#dependent-task').change(function() {
            $('#dependent-fields').toggleClass('d-none');
        });

        $('.toggle-other-details').click(function() {
            $(this).find('svg').toggleClass('fa-chevron-down fa-chevron-up');
            $('#other-details').toggleClass('d-none');
        });

        $('#createTaskLabel').click(function() {
            const url = "{{ route('task-label.create') }}";
            $(MODAL_XL + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_XL, url);
        });

        $('#add-project').click(function() {
            $(MODAL_XL).modal('show');
            const url = "{{ route('projects.create') }}";
            $.easyAjax({
                url: url,
                blockUI: true,
                container: MODAL_XL,
                success: function(response) {
                    if (response.status == "success") {
                        $(MODAL_XL + ' .modal-body').html(response.html);
                        $(MODAL_XL + ' .modal-title').html(response.title);
                        init(MODAL_XL);
                    }
                }
            });
        });

         // GET STAGES
         $('#editPipeline').change(function (e) {
            let pipelineId = $(this).val();
            var url = "{{ route('deals.get-stage', ':id') }}";
            url = url.replace(':id', pipelineId);

            $.easyAjax({
                url: url,
                type: "GET",
                success: function (response) {
                    if (response.status == 'success') {
                        var options = [];
                        var rData = [];
                        rData = response.data;
                        $.each(rData, function (index, value) {
                            var selectData = '';
                            selectData = `<option data-content="<i class='fa fa-circle' style='color: ${value.label_color}'></i> ${value.name} " value="${value.id}"> ${value.name}</option>`;
                            options.push(selectData);
                        });

                        $('#stages').html(options);
                        $('#stages').selectpicker('refresh');
                    }
                }
            })
        });

        $('#add-employee').click(function() {
            $(MODAL_XL).modal('show');

            const url = "{{ route('employees.create') }}";

            $.easyAjax({
                url: url,
                blockUI: true,
                container: MODAL_XL,
                success: function(response) {
                    if (response.status == "success") {
                        $(MODAL_XL + ' .modal-body').html(response.html);
                        $(MODAL_XL + ' .modal-title').html(response.title);
                        init(MODAL_XL);
                    }
                }
            });
        });

        <x-forms.custom-field-filejs/>

        init(RIGHT_MODAL);
    });

</script>
