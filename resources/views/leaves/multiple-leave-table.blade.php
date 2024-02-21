<x-table class="table-sm-responsive table mb-0">
    <x-slot name="thead">
        <th>@lang('app.leaveDate')</th>
        <th>@lang('app.leaveType')</th>
        <th>@lang('app.status')</th>
        @if ($approveRejectPermission == 'all' || ($deleteLeavePermission == 'all'
                                || ($deleteLeavePermission == 'added' && user()->id == $leave->added_by)
                                || ($deleteLeavePermission == 'owned' && user()->id == $leave->user_id)
                                || ($deleteLeavePermission == 'both' && (user()->id == $leave->user_id || user()->id == $leave->added_by))
                                || ($leaveSetting->manager_permission != 'cannot-approve' && user()->id == $multipleLeaves->first()->user->employeeDetails->reporting_to)
                                ))
            <th class="text-right pr-20">@lang('app.action')</th>
        @endif
    </x-slot>

    @forelse($multipleLeaves as $leave)
        <tr class="row{{ $leave->id }}">
            <td>
                {{$leave->leave_date->translatedFormat(company()->date_format)}}
            </td>
            <td>
                <span class="badge badge-success" style="background-color:{{$leave->type->color}}">{{ $leave->type->type_name }}</span>
            </td>
            <td>
                @php
                    if ($leave->status == 'approved') {
                        $class = 'text-light-green';
                        $status = __('app.approved');
                    }
                    else if ($leave->status == 'pending') {
                        $class = 'text-yellow';
                        $status = __('app.pending');
                    }
                    else {
                        $class = 'text-red';
                        $status = __('app.rejected');
                    }
                @endphp

                <i class="fa fa-circle mr-1 {{$class}} f-10"></i> {{$status}}
            </td>
{{--            @dd($leaveSetting->manager_permission != 'cannot-approve', user()->id == $leave->user->employeeDetails->reporting_to)--}}
            @if ($approveRejectPermission == 'all' || ($deleteLeavePermission == 'all'
                                || ($deleteLeavePermission == 'added' && user()->id == $leave->added_by)
                                || ($deleteLeavePermission == 'owned' && user()->id == $leave->user_id)
                                || ($deleteLeavePermission == 'both' && (user()->id == $leave->user_id || user()->id == $leave->added_by))
                                ) || ($leaveSetting->manager_permission != 'cannot-approve' && user()->id == $leave->user->employeeDetails->reporting_to)
                                )
                @if($viewType == 'model')
                    <td class="text-right">
                        @if ($leave->status == 'pending' && ($approveRejectPermission == 'all' || ($leaveSetting->manager_permission != 'cannot-approve' && user()->id == $leave->user->employeeDetails->reporting_to)))
                            <div class="task_view">
                                <a class="dropdown-item leave-action-approved action-hover" data-leave-id={{ $leave->id }}
                                    data-leave-action="approved" data-toggle="tooltip" data-original-title="@lang('app.approve')" data-leave-type-id="{{ $leave->leave_type_id }}" href="javascript:;">
                                        <i class="fa fa-check mr-2"></i>
                                </a>
                            </div>
                            <div class="task_view mt-1 mt-lg-0 mt-md-0">
                                <a class="dropdown-item leave-action-reject action-hover" data-leave-id={{ $leave->id }}
                                    data-leave-action="rejected" data-toggle="tooltip" data-original-title="@lang('app.reject')" data-leave-type-id="{{ $leave->leave_type_id }}"  href="javascript:;">
                                        <i class="fa fa-times mr-2"></i>
                                </a>
                            </div>
                        @endif
                        @if ($deleteLeavePermission == 'all'
                                    || ($deleteLeavePermission == 'added' && user()->id == $leave->added_by)
                                    || ($deleteLeavePermission == 'owned' && user()->id == $leave->user_id)
                                    || ($deleteLeavePermission == 'both' && (user()->id == $leave->user_id || user()->id == $leave->added_by)))
                            <div class="task_view mt-1 mt-lg-0 mt-md-0">
                                <a data-leave-id={{$leave->id}} data-type="multiple-leave" data-unique-id="{{$leave->unique_id}}"
                                    class="dropdown-item delete-table-row action-hover"  data-toggle="tooltip" data-original-title="@lang('app.delete')" href="javascript:;">
                                    <i class="fa fa-trash mr-2"></i>
                                </a>
                            </div>
                        @endif
                    </td>
                @else
                    <td class="text-right pr-20">
                        <div class="task_view">
                            <div class="dropdown">
                                <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="dropdownMenuLink-41" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-options-vertical icons"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-41" tabindex="0" x-placement="bottom-end" style="position: absolute; transform: translate3d(-137px, 26px, 0px); top: 0px; left: 0px; will-change: transform;">
                                    <a href="{{route('leaves.show', $leave->id) }}?type=single" class="dropdown-item openRightModal"><i class="fa fa-eye mr-2"></i>@lang('app.view')</a>

                                    @if ($leave->status == 'pending')
                                        <a class="dropdown-item leave-action-approved" data-leave-id={{ $leave->id }}
                                            data-leave-action="approved" data-user-id="{{ $leave->user_id }}" data-leave-type-id="{{ $leave->leave_type_id }}" href="javascript:;">
                                            <i class="fa fa-check mr-2"></i>@lang('app.approve')
                                        </a>
                                        <a data-leave-id={{ $leave->id }}
                                                data-leave-action="rejected" data-user-id="{{ $leave->user_id }}" data-leave-type-id="{{ $leave->leave_type_id }}" class="dropdown-item leave-action-reject" href="javascript:;">
                                                <i class="fa fa-times mr-2"></i>@lang('app.reject')
                                        </a>
                                        @if ($editLeavePermission == 'all'
                                        || ($editLeavePermission == 'added' && user()->id == $leave->added_by)
                                        || ($editLeavePermission == 'owned' && user()->id == $leave->user_id)
                                        || ($editLeavePermission == 'both' && (user()->id == $leave->user_id || user()->id == $leave->added_by))
                                        )
                                            <div class="mt-1 mt-lg-0 mt-md-0">
                                                <a class="dropdown-item openRightModal" href="{{ route('leaves.edit', $leave->id) }}">
                                                    <i class="fa fa-edit mr-2"></i>@lang('app.edit')
                                            </a>
                                            </div>
                                        @endif
                                    @endif

                                    @if ($deleteLeavePermission == 'all'
                                    || ($deleteLeavePermission == 'added' && user()->id == $leave->added_by)
                                    || ($deleteLeavePermission == 'owned' && user()->id == $leave->user_id)
                                    || ($deleteLeavePermission == 'both' && (user()->id == $leave->user_id || user()->id == $leave->added_by)))
                                        <div class="mt-1 mt-lg-0 mt-md-0">
                                            <a data-leave-id="{{ $leave->id }}" data-unique-id=" {{ $leave->unique_id }}"
                                                data-duration="{{ $leave->duration }}" class="dropdown-item delete-multiple-leave" href="javascript:;">
                                                <i class="fa fa-trash mr-2"></i>@lang('app.delete')
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                @endif
            @endif
        </tr>
    @empty
        <tr>
            <td colspan="4">
                <x-cards.no-record icon="user" :message="__('messages.noAgentAdded')" />
            </td>
        </tr>
    @endforelse
</x-table>
