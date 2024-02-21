@php
$editPipelinePermission = user()->permission('edit_deal_pipeline');
$mangeLeadStagePermission = user()->permission('manage_deal_stages');
@endphp
<div class="table-responsive p-20 pipelineData">

    <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100">
        @forelse($pipelines as $pipeline)
            <div class="row no-gutters border rounded my-3 px-4 py-2">
                <div class="col-md-6">
                    <div class="heading-h4"><x-status :value="$pipeline->name" :style="'color:'.$pipeline->label_color" />
                    @if($editPipelinePermission == 'all'
                    || ($editPipelinePermission == 'added' && user()->id == $pipeline->added_by)
                    || ($editPipelinePermission == 'owned' && user()->id == $pipeline->added_by)
                    || ($editPipelinePermission == 'both' && user()->id == $pipeline->added_by))
                        <a href="javascript:;" title="@lang('app.edit')" data-pipeline-id="{{ $pipeline->id }}"
                        class="edit-pipeline "> <i class="fa fa-edit icons mr-2"></i>
                        </a>
                    @endif
                </div>

                    <div class="simple-text text-lightest mt-1">{{ $pipeline->stages->count() }} @lang('modules.deal.stages')
                    </div>
                </div>
                <div class="col-md-4">
                    <x-forms.radio fieldId="pipelineid_{{ $pipeline->id }}" class="set_default_pipeline"
                        data-pipeline-id="{{ $pipeline->id }}" :fieldLabel="__('app.default')"
                        fieldName="pipeline" fieldValue="{{ $pipeline->id }}"
                        :checked="($pipeline->default == 1) ? 'checked' : ''">
                    </x-forms.radio>
                </div>
                <div class="col-md-2 text-right" >
                    <x-forms.button-secondary class="view-pipeline" data-pipeline-id="{{ $pipeline->id }}"> <i class="side-icon bi bi-kanban"></i>
                        @lang('modules.deal.stages')
                    </x-forms.button-secondary>
                </div>
            </div>
            <div class="table-sm-responsive role-permissions d-none" id="pipeline-stages-{{ $pipeline->id }}">
                <x-table class="table-bordered">
                    <x-slot name="thead">
                        <th>#</th>
                        <th>@lang('modules.deal.leadStage')</th>
                        <th>@lang('modules.deal.defaultLeadStage')</th>
                        <th>@lang('app.action')</th>
                    </x-slot>

                    @forelse($pipeline->stages as $key => $stage)
                        <tr class="row{{ $stage->id }}">
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <x-status :value="$stage->name" :style="'color:'.$stage->label_color" />
                            </td>

                            <td>
                                <x-forms.radio fieldId="pipeline_id_{{ $stage->id }}" class="set_default_stage"
                                    data-status-id="{{ $stage->id }}" data-pipeline-id="{{ $stage->pipeline->id }}"  :fieldLabel="__('app.default')"
                                    fieldName="{{$stage->pipeline->name}}" fieldValue="{{ $stage->id }}"
                                    :checked="($stage->default == 1) ? 'checked' : ''">
                                </x-forms.radio>
                            </td>

                            <td>
                                <div class="task_view">
                                    <a href="javascript:;" data-status-id="{{ $stage->id }}"
                                        class="edit-status task_view_more d-flex align-items-center justify-content-center"> <i
                                            class="fa fa-edit icons mr-2"></i> @lang('app.edit')
                                    </a>
                                </div>

                                @if (!$stage->default && $stage->slug != 'generated' &&  $stage->slug != 'win' && $stage->slug != 'lost' )
                                    <div class="task_view mt-1 mt-lg-0 mt-md-0">
                                        <a href="javascript:;"
                                            class="delete-stage task_view_more d-flex align-items-center justify-content-center"
                                            data-stage-id="{{ $stage->id }}">
                                            <i class="fa fa-trash icons mr-2"></i> @lang('app.delete')
                                        </a>
                                    </div>
                                @endif

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-cards.no-record icon="list" :message="__('messages.noLeadStatusAdded')" />
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </div>
        @empty
        @endforelse


    </div>
</div>

<script>
     $('.pipelineData').on('click', '.view-pipeline', function() {

            var pipelineId = $(this).data('pipeline-id');
            $("#pipeline-stages-"+pipelineId).toggleClass('d-none');

        });
</script>
