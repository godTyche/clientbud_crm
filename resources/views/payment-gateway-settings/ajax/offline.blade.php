<div class="col-xl-12 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-20">
    <div class="row">
        <div class="table-responsive">

            <x-table class="table-bordered">
                <x-slot name="thead">
                    <th>#</th>
                    <th width="20%">@lang('app.menu.method')</th>
                    <th width="45%">@lang('app.description')</th>
                    <th>@lang('app.status')</th>
                    <th class="text-right">@lang('app.action')</th>
                </x-slot>

                @forelse($offlineMethods as $key => $method)
                    <tr class="row{{ $method->id }}">
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $method->name }}</td>
                        <td class="text-break">{!! nl2br($method->description) !!} </td>
                        <td>
                            <i @class([
                            'fa fa-circle mr-1 f-10',
                            'text-light-green' => $method->status == 'yes',
                            'text-red' => $method->status !== 'yes',
                            ]) ></i>
                            {{ ($method->status == 'yes') ? __('app.active'): __('app.inactive') }}
                        </td>

                        <td class="text-right">
                            <div class="task_view">
                                <a class="task_view_more d-flex align-items-center justify-content-center edit-type"
                                   href="javascript:;" data-type-id="{{ $method->id }}">
                                    <i class="fa fa-edit icons mr-2"></i> @lang('app.edit')
                                </a>
                            </div>
                            <div class="task_view">
                                <a class="task_view_more d-flex align-items-center justify-content-center delete-type"
                                   href="javascript:;" data-type-id="{{ $method->id }}">
                                    <i class="fa fa-trash icons mr-2"></i> @lang('app.delete')
                                </a>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">
                            <x-cards.no-record icon="key" :message="__('messages.norecordSaved')" />
                        </td>
                    </tr>
                @endforelse
            </x-table>

        </div>
    </div>
</div>
