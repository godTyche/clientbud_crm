<div class="card w-100 rounded-0 border-0 comment">
    <div class="card-horizontal">
        <div class="card-body border-0 pl-0 py-1">
            @forelse ($leaveTypes as $key=>$leave)
                @if($leave->leaveTypeCodition($leave, $userRole))
                    <div class="card-text f-14 text-dark-grey text-justify">
                        <x-table class="table-bordered my-3 rounded">
                            <x-slot name="thead">
                                <th>@lang('modules.leaves.leaveType')</th>
                                <th>@lang('modules.leaves.noOfLeaves')</th>
                                <th>@lang('modules.leaves.monthLimit')</th>
                                <th class="text-right">@lang('app.totalLeavesTaken')</th>
                            </x-slot>

                            <tr>
                                <td width="25%">
                                    <x-status :value="$leave->type_name" :style="'color:'.$leave->color" />
                                </td>
                                <td width="25%">{{ isset($employeeLeavesQuota[$key]) ? $employeeLeavesQuota[$key]->no_of_leaves : 0 }}</td>
                                <td width="25%">{{ ($leave->monthly_limit > 0) ? $leave->monthly_limit : '--' }}</td>
                                <td class="text-right" width="25%">
                                    {{ (!is_null($leave->leavesCount)) ? $leave->leavesCount->count - ($leave->leavesCount->halfday*0.5) : '0' }}
                                </td>
                            </tr>
                        </x-table>
                    </div>
                @endif
            @empty
                <x-cards.no-record icon="redo" :message="__('messages.noRecordFound')" />
            @endforelse
        </div>
    </div>
</div>
