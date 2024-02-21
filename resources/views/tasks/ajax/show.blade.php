@php
$editTaskPermission = user()->permission('edit_tasks');
$sendReminderPermission = user()->permission('send_reminder');
$changeStatusPermission = user()->permission('change_status');
$viewProjectPermission = user()->permission('view_projects');

@endphp

<div id="task-detail-section">

    <h3 class="heading-h1 mb-3">{{ $task->heading }}</h3>
    <div class="row">
        <div class="col-sm-9">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white  border-bottom-grey text-capitalize justify-content-between p-20">
                    <div class="row">
                        <div class="col-lg-8 col-10">
                            @if ($changeStatusPermission == 'all'
                            || ($changeStatusPermission == 'added' && $task->added_by == user()->id)
                            || ($changeStatusPermission == 'owned' && in_array(user()->id, $taskUsers))
                            || ($changeStatusPermission == 'both' && (in_array(user()->id, $taskUsers) || $task->added_by == user()->id))
                            || ($task->project && $task->project->project_admin == user()->id)
                            )
                                @if ($task->boardColumn->slug != 'completed')
                                    <x-forms.button-primary icon="check" data-status="completed"
                                        class="change-task-status mr-2 mb-2 mb-lg-0 mb-md-0">
                                        @lang('modules.tasks.markComplete')
                                    </x-forms.button-primary>
                                @else
                                    <x-forms.button-secondary icon="times" data-status="incomplete"
                                        class="change-task-status mr-3">
                                        @lang('modules.tasks.markIncomplete')
                                    </x-forms.button-secondary>
                                @endif
                            @endif

                            @if ($task->boardColumn->slug != 'completed' && !is_null($task->is_task_user) && in_array('timelogs', user_modules()))
                                @if (is_null($task->userActiveTimer))
                                    <x-forms.button-secondary id="start-task-timer" icon="play">
                                        @lang('modules.timeLogs.startTimer')
                                    </x-forms.button-secondary>
                                @elseif (!is_null($task->userActiveTimer))

                                    <span class="border p-2 rounded mr-2 bg-light"><i class="fa fa-clock mr-1"></i><span id="active-task-timer">{{ $task->userActiveTimer->timer }}</span></span>

                                    @if (is_null($task->userActiveTimer->activeBreak))
                                        <x-forms.button-secondary icon="pause-circle" data-time-id="{{ $task->userActiveTimer->id }}" id="pause-timer-btn" class="mr-2" data-url="{{ url()->current() }}">@lang('modules.timeLogs.pauseTimer')</x-forms.button-secondary>

                                        <x-forms.button-secondary data-time-id="{{ $task->userActiveTimer->id }}"
                                            id="stop-task-timer" icon="stop-circle" data-url="{{ url()->current() }}">
                                            @lang('modules.timeLogs.stopTimer')
                                        </x-forms.button-secondary>
                                    @else
                                        <x-forms.button-secondary id="resume-timer-btn" icon="play-circle" data-url="{{ url()->current() }}"
                                        data-time-id="{{ $task->userActiveTimer->activeBreak->id }}">@lang('modules.timeLogs.resumeTimer')</x-forms.button-secondary>
                                    @endif

                                @endif
                            @endif
                        </div>
                        <div class="col-lg-4 col-2 text-right">
                            <div class="dropdown">
                                <button
                                    class="btn btn-lg f-14 px-2 py-1 text-dark-grey text-capitalize rounded  dropdown-toggle"
                                    type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                                    aria-labelledby="dropdownMenuLink" tabindex="0">

                                    @if ($sendReminderPermission == 'all' && $task->boardColumn->slug != 'completed')
                                        <a class="dropdown-item" id="reminderButton"
                                            href="javascript:;">@lang('modules.tasks.reminder')</a>
                                    @endif

                                    @if ($editTaskPermission == 'all' || ($editTaskPermission == 'added' && $task->added_by == user()->id) || ($task->project && $task->project->project_admin == user()->id))
                                        <a class="dropdown-item openRightModal"
                                            href="{{ route('tasks.edit', $task->id) }}">@lang('app.edit')
                                            @lang('app.task')</a>

                                        <hr class="my-1">
                                    @endif

                                    @php $pin = $task->pinned() @endphp

                                    @if ($pin)
                                        <a class="dropdown-item" href="javascript:;" id="pinnedItem"
                                            data-pinned="pinned">@lang('app.unpin')
                                            @lang('app.task')</a>
                                    @else
                                        <a class="dropdown-item" href="javascript:;" id="pinnedItem"
                                            data-pinned="unpinned">@lang('app.pin')
                                            @lang('app.task')</a>
                                    @endif

                                    @if (($taskSettings->copy_task_link == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                                        <a class="dropdown-item btn-copy" href="javascript:;"
                                        data-clipboard-text="{{ route('front.task_detail', $task->hash) }}">@lang('modules.tasks.copyTaskLink')</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (($taskSettings->project == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                        <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                            <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">@lang('app.project')</p>
                            <p class="mb-0 text-dark-grey f-14 w-70">
                                @if ($task->project_id)
                                    @if ($task->project->status == 'in progress')
                                        <i class="fa fa-circle mr-1 text-blue f-10"></i>
                                    @elseif ($task->project->status == 'on hold')
                                        <i class="fa fa-circle mr-1 text-yellow f-10"></i>
                                    @elseif ($task->project->status == 'not started')
                                        <i class="fa fa-circle mr-1 text-yellow f-10"></i>
                                    @elseif ($task->project->status == 'canceled')
                                        <i class="fa fa-circle mr-1 text-red f-10"></i>
                                    @elseif ($task->project->status == 'finished')
                                        <i class="fa fa-circle mr-1 text-dark-green f-10"></i>
                                    @endif
                                    @if ($viewProjectPermission == 'all'
                                    || ($viewProjectPermission == 'added' && $task->project->added_by == user()->id)
                                    || ($viewProjectPermission == 'owned' && user()->id == $task->project->client_id && in_array('client', user_roles()))
                                    || ($viewProjectPermission == 'both' && (user()->id == $task->project->client_id || user()->id == $task->added_by))
                                    )
                                    <a href="{{ route('projects.show', $task->project_id) }}" class="text-dark-grey">
                                        {{ $task->project->project_name }}</a>
                                    @else
                                    {{ $task->project->project_name }}
                                    @endif
                                @else
                                    --
                                @endif
                            </p>

                        </div>
                    @endif

                    @if (($taskSettings->priority == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()) )
                        <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                            <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                                @lang('modules.tasks.priority')</p>
                                <p class="mb-0 text-dark-grey f-14 w-70">
                                    @if ($task->priority == 'high')
                                    <i class="fa fa-circle mr-1 text-red f-10"></i>
                                    @elseif ($task->priority == 'medium')
                                    <i class="fa fa-circle mr-1 text-yellow f-10"></i>
                                    @else
                                    <i class="fa fa-circle mr-1 text-dark-green f-10"></i>
                                    @endif
                                    @lang('app.'.$task->priority)
                                </p>
                        </div>
                    @endif

                    @if (($taskSettings->assigned_to == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                        <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                            <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                                @lang('modules.tasks.assignTo')</p>
                                @if (count($task->users) > 0)
                                    @if (count($task->users) > 1)
                                        @foreach ($task->users as $item)
                                            <div class="taskEmployeeImg rounded-circle mr-1">
                                                <a href="{{ route('employees.show', $item->id) }}">
                                                    <img data-toggle="tooltip" data-original-title="{{ $item->name }}"
                                                        src="{{ $item->image_url }}">
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        @foreach ($task->users as $item)
                                            <x-employee :user="$item" />
                                        @endforeach
                                    @endif
                                @else
                                --
                            @endif
                        </div>
                    @endif

                    <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                        <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">@lang('modules.taskShortCode')</p>
                        <p class="mb-0 text-dark-grey f-14 w-70">
                           {{ ($task->task_short_code) ? $task->task_short_code : '--' }}
                        </p>
                    </div>

                    <x-cards.data-row :label="__('modules.projects.milestones')" :value="$task->milestone->milestone_title ?? '--'" />

                    @if (($taskSettings->assigned_by == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                        @if ($task->created_by)
                            <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                                <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                                    @lang('modules.tasks.assignBy')</p>
                                {{-- <p class="mb-0 text-dark-grey f-14 w-70"> --}}
                                <x-employee :user="$task->createBy" />
                                {{-- </p> --}}
                            </div>
                        @endif
                    @endif

                    @if (($taskSettings->label == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                        <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                            <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                                @lang('app.label')</p>
                            <p class="mb-0 text-dark-grey f-14 w-70">
                                @forelse ($task->labels as $key => $label)
                                    <span class='badge badge-secondary'
                                        style='background-color: {{ $label->label_color }}'>{{ $label->label_name }} </span>
                                        @if ($label->description)
                                            <i class="fa fa-question-circle" data-toggle="popover" data-placement="top" data-content="{{ $label->description }}" data-html="true" data-trigger="hover"></i>
                                        @endif
                                @empty
                                    --
                                @endforelse
                            </p>
                        </div>
                    @endif

                    @if (in_array('gitlab', user_modules()) && isset($gitlabIssue))
                        <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                            <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                                GitLab</p>
                            <div class="mb-0 w-70">
                                <div class='card border'>
                                    <div class="card-body bg-white d-flex justify-content-between p-2 align-items-center rounded">
                                        <h4 class="f-13 f-w-500 mb-0">
                                            <img src="{{ asset('img/gitlab-icon-rgb.png') }}" class="height-35">
                                            <a href="{{ $gitlabIssue['web_url'] }}" class="text-darkest-grey f-w-500" target="_blank">#{{ $gitlabIssue['iid'] }} {{ $gitlabIssue['title'] }} <i class="fa fa-external-link-alt"></i></a>
                                        </h4>
                                        <div>
                                            <span class="badge badge-{{ $gitlabIssue['state'] == 'opened' ? 'danger' : 'success' }}">{{ $gitlabIssue['state'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                        @if (($taskSettings->task_category == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                            <x-cards.data-row :label="__('modules.tasks.taskCategory')"
                                              :value="$task->category->category_name ?? '--'" html="true"/>
                        @endif

                        @if (($taskSettings->description == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                            <x-cards.data-row :label="__('app.description')"
                                              :value="!empty($task->description) ? $task->description : '--'"
                                              html="true"/>
                        @endif

                        {{-- Custom fields data --}}
                        @if (($taskSettings->custom_fields == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                            <x-forms.custom-field-show :fields="$fields" :model="$task"></x-forms.custom-field-show>
                        @endif

                </div>
            </div>

            @if ((($taskSettings->files == 'yes' || $taskSettings->sub_task == 'yes' || $taskSettings->comments == 'yes'|| $taskSettings->time_logs == 'yes'|| $taskSettings->notes == 'yes' || $taskSettings->history == 'yes') && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                <!-- TASK TABS START -->
                <div class="bg-additional-grey rounded my-3">

                    <div class="s-b-inner s-b-notifications bg-white b-shadow-4 rounded">

                        <x-tab-section class="task-tabs">

                            @if (($taskSettings->files == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                                <x-tab-item class="ajax-tab" :active="(request('view') === 'file' || !request('view'))"
                                :link="route('tasks.show', $task->id).'?view=file'">@lang('app.file')</x-tab-item>
                            @endif

                            @if (($taskSettings->sub_task == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                                <x-tab-item class="ajax-tab" :active="(request('view') === 'sub_task')"
                                :link="route('tasks.show', $task->id).'?view=sub_task'">
                                @lang('modules.tasks.subTask')</x-tab-item>
                            @endif

                            @if (($taskSettings->comments == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                                @if ($viewTaskCommentPermission != 'none')
                                    <x-tab-item class="ajax-tab" :active="(request('view') === 'comments')"
                                        :link="route('tasks.show', $task->id).'?view=comments'">
                                        @lang('modules.tasks.comment')</x-tab-item>
                                @endif
                            @endif

                            @if ((($taskSettings->time_logs == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles())) && in_array('timelogs', user_modules()))
                                <x-tab-item class="ajax-tab" :active="(request('view') === 'time_logs')"
                                    :link="route('tasks.show', $task->id).'?view=time_logs'">
                                    @lang('app.menu.timeLogs')
                                    @if ($task->active_timer_all_count > 0)
                                        <i class="fa fa-clock text-primary f-12 ml-1"></i>
                                    @endif
                                </x-tab-item>
                            @endif

                            @if (($taskSettings->notes == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                                @if ($viewTaskNotePermission != 'none')
                                    <x-tab-item class="ajax-tab" :active="(request('view') === 'notes')"
                                    :link="route('tasks.show', $task->id).'?view=notes'">@lang('app.notes')</x-tab-item>
                                @endif
                            @endif

                            @if (($taskSettings->history == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                                <x-tab-item class="ajax-tab" :active="(request('view') === 'history')"
                                    :link="route('tasks.show', $task->id).'?view=history'">@lang('modules.tasks.history')
                                </x-tab-item>
                            @endif
                        </x-tab-section>


                        <div class="s-b-n-content">
                            <div class="tab-content" id="nav-tabContent">
                                @include($tab)
                            </div>
                        </div>
                    </div>


                </div>
                <!-- TASK TABS END -->
            @endif



        </div>

        <div class="col-sm-3">
            <x-cards.data>
                @if (($taskSettings->status == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                    <p class="f-w-500"><i class="fa fa-circle mr-1 text-yellow"
                            style="color: {{ $task->boardColumn->label_color }}"></i>{{ $task->boardColumn->column_name }}
                    </p>
                @endif

                @if (($taskSettings->make_private == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                    @if ($task->is_private || $pin)
                        <div class="col-12 px-0 pb-3 d-flex">
                            @if ($task->is_private)
                                <span class='badge badge-secondary'><i class='fa fa-lock'></i>
                                    @lang('app.private')</span>&nbsp;
                            @endif

                            @if ($pin)
                                <span class='badge badge-success'><i class='fa fa-thumbtack'></i> @lang('app.pinned')</span>
                            @endif
                        </div>
                    @endif
                @endif

                @if (($taskSettings->start_date == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                    <div class="col-12 px-0 pb-3 d-lg-flex d-block">
                        <p class="mb-0 text-lightest w-50 f-14 text-capitalize">{{ __('app.startDate') }}
                        </p>
                        <p class="mb-0 text-dark-grey w-50 f-14">
                            @if(!is_null($task->start_date))
                                {{ $task->start_date->translatedFormat(company()->date_format) }}
                            @else
                                --
                            @endif
                        </p>
                    </div>
                @endif

                @if (($taskSettings->due_date == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                    <div class="col-12 px-0 pb-3 d-lg-flex d-block">
                        <p class="mb-0 text-lightest w-50 f-14 text-capitalize">{{ __('app.dueDate') }}
                        </p>
                        <p class="mb-0 text-dark-grey w-50 f-14">
                            @if(!is_null($task->due_date))
                                {{ $task->due_date->translatedFormat(company()->date_format) }}
                            @else
                                --
                            @endif
                        </p>
                    </div>
                @endif

                @if (($taskSettings->time_estimate == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles()))
                    @if ($task->estimate_hours > 0 || $task->estimate_minutes > 0)
                        <div class="col-12 px-0 pb-3 d-lg-flex d-block">
                            <p class="mb-0 text-lightest w-50 f-14 text-capitalize">
                                {{ __('modules.tasks.setTimeEstimate') }}
                            </p>
                            <p class="mb-0 text-dark-grey w-50 f-14">{{ $task->estimate_hours }} @lang('app.hrs') {{ $task->estimate_minutes }} @lang('app.mins')</p>
                        </div>
                    @endif
                @endif

                @php
                    $activeTimerMinutes = 0;
                    $activeBreakMinutes = 0;
                @endphp
                @if ($task->activeTimerAll)
                    @foreach ($task->activeTimerAll as $item)
                        @php
                            $activeTimerMinutes = $activeTimerMinutes + (($item->activeBreak) ? $item->activeBreak->start_time->diffInMinutes($item->start_time) : now()->diffInMinutes($item->start_time));
                            $activeBreakMinutes = $activeBreakMinutes + $item->breaks->sum('total_minutes');
                        @endphp
                    @endforeach
                @endif

                @php
                    $totalMinutes = $task->timeLogged->sum('total_minutes') + $activeTimerMinutes - $breakMinutes - $activeBreakMinutes;
                    $timeLog = \Carbon\CarbonInterval::formatHuman($totalMinutes);
                @endphp

                @if ((($taskSettings->hours_logged == 'yes' && in_array('client', user_roles())) || in_array('admin', user_roles()) || in_array('employee', user_roles())) && in_array('timelogs', user_modules()))
                    <div class="col-12 px-0 pb-3 d-lg-flex d-block">
                        <p class="mb-0 text-lightest w-50 f-14 text-capitalize">
                            {{ __('modules.employees.hoursLogged') }}
                        </p>
                        <p class="mb-0 text-dark-grey w-50 f-14">{{ $timeLog }}</p>
                    </div>
                @endif
            </x-cards.data>

        </div>

    </div>

    <script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>
    <script>
        var clipboard = new ClipboardJS('.btn-copy');

        clipboard.on('success', function(e) {
            Swal.fire({
                icon: 'success',
                text: '@lang("app.copied")',
                toast: true,
                position: 'top-end',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
            })
        });
    </script>

    <script>
        $(document).ready(function() {

            var $worked = $("#active-task-timer");

            function updateTimer() {
                var myTime = $worked.html();
                var ss = myTime.split(":");

                var hours = ss[0];
                var mins = ss[1];
                var secs = ss[2];
                secs = parseInt(secs) + 1;

                if (secs > 59) {
                    secs = '00';
                    mins = parseInt(mins) + 1;
                }

                if (mins > 59) {
                    secs = '00';
                    mins = '00';
                    hours = parseInt(hours) + 1;
                }

                if (hours.toString().length < 2) {
                    hours = '0' + hours;
                }
                if (mins.toString().length < 2) {
                    mins = '0' + mins;
                }
                if (secs.toString().length < 2) {
                    secs = '0' + secs;
                }
                var ts = hours + ':' + mins + ':' + secs;

                $worked.html(ts);
                setTimeout(updateTimer, 1000);
            }
            if ($('#stop-task-timer').length) {
                setTimeout(updateTimer, 1000);
            }

            //    change task status
            $('body').on('click', '.change-task-status', function() {
                var status = $(this).data('status');

                var id = '{{ $task->id }}';

                if (status == 'completed') {
                    var checkUrl = "{{ route('tasks.check_task', ':id') }}";
                    checkUrl = checkUrl.replace(':id', id);
                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        url: checkUrl,
                        type: "POST",
                        blockUI: true,
                        container: '#task-detail-section',
                        data: {
                            '_token': token
                        },
                        success: function(data) {
                            if (data.taskCount > 0) {
                                Swal.fire({
                                    title: "@lang('messages.sweetAlertTitle')",
                                    text: "@lang('messages.markCompleteTask')",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    focusConfirm: false,
                                    confirmButtonText: "@lang('messages.completeIt')",
                                    cancelButtonText: "@lang('app.cancel')",
                                    customClass: {
                                        confirmButton: 'btn btn-primary mr-3',
                                        cancelButton: 'btn btn-secondary'
                                    },
                                    showClass: {
                                        popup: 'swal2-noanimation',
                                        backdrop: 'swal2-noanimation'
                                    },
                                    buttonsStyling: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        updateTask(id, status);
                                    }
                                });

                            } else {
                                updateTask(id, status)
                            }

                        }
                    });
                } else {
                    updateTask(id, status)
                }


            });

            $('body').on('click', '#pinnedItem', function() {
                var type = $('#pinnedItem').attr('data-pinned');
                var id = '{{ $task->id }}';
                var pinType = 'task';

                var dataPin = type.trim(type);
                if (dataPin == 'pinned') {
                    Swal.fire({
                        title: "@lang('messages.sweetAlertTitle')",
                        icon: 'warning',
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonText: "@lang('messages.confirmUnpin')",
                        cancelButtonText: "@lang('app.cancel')",
                        customClass: {
                            confirmButton: 'btn btn-primary mr-3',
                            cancelButton: 'btn btn-secondary'
                        },
                        showClass: {
                            popup: 'swal2-noanimation',
                            backdrop: 'swal2-noanimation'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var url = "{{ route('tasks.destroy_pin', ':id') }}";
                            url = url.replace(':id', id);

                            var token = "{{ csrf_token() }}";
                            $.easyAjax({
                                type: 'POST',
                                url: url,
                                data: {
                                    '_token': token,
                                    'type': pinType
                                },
                                success: function(response) {
                                    if (response.status == "success") {
                                        window.location.reload();
                                    }
                                }
                            })
                        }
                    });

                } else {
                    Swal.fire({
                        title: "@lang('messages.sweetAlertTitle')",
                        icon: 'warning',
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonText: "@lang('messages.confirmPin')",
                        cancelButtonText: "@lang('app.cancel')",
                        customClass: {
                            confirmButton: 'btn btn-primary mr-3',
                            cancelButton: 'btn btn-secondary'
                        },
                        showClass: {
                            popup: 'swal2-noanimation',
                            backdrop: 'swal2-noanimation'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var url = "{{ route('tasks.store_pin') }}?type=" + pinType;

                            var token = "{{ csrf_token() }}";
                            $.easyAjax({
                                type: 'POST',
                                url: url,
                                data: {
                                    '_token': token,
                                    'task_id': id
                                },
                                success: function(response) {
                                    if (response.status == "success") {
                                        window.location.reload();
                                    }
                                }
                            });
                        }
                    });
                }
            });

            $(".ajax-tab").click(function(event) {
                event.preventDefault();

                $('.task-tabs .ajax-tab').removeClass('active');
                $(this).addClass('active');

                const requestUrl = this.href;

                $.easyAjax({
                    url: requestUrl,
                    blockUI: true,
                    container: "#nav-tabContent",
                    historyPush: ($(RIGHT_MODAL).hasClass('in') ? false : true),
                    data: {
                        'json': true
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            $('#nav-tabContent').html(response.html);
                        }
                    }
                });
            });

            // Update Task
            function updateTask(id, status) {
                var url = "{{ route('tasks.change_status') }}";
                var token = "{{ csrf_token() }}";
                $.easyAjax({
                    url: url,
                    type: "POST",
                    async: false,
                    data: {
                        '_token': token,
                        taskId: id,
                        status: status,
                        sortBy: 'id'
                    },
                    success: function(data) {
                        window.location.reload();
                    }
                })
            }


            $('body').on('click', '.delete-comment', function() {
                var id = $(this).data('row-id');
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('taskComment.destroy', ':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {
                                '_token': token,
                                '_method': 'DELETE'
                            },
                            success: function(response) {
                                if (response.status == "success") {
                                    $('#comment-list').html(response.view);
                                }
                            }
                        });
                    }
                });
            });

            $('body').on('click', '.edit-comment', function() {
                var id = $(this).data('row-id');
                var url = "{{ route('taskComment.edit', ':id') }}";
                url = url.replace(':id', id);
                $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
                $.ajaxModal(MODAL_LG, url);
            });

            $('body').on('click', '.delete-subtask', function() {
                var id = $(this).data('row-id');
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('sub-tasks.destroy', ':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {
                                '_token': token,
                                '_method': 'DELETE'
                            },
                            success: function(response) {
                                if (response.status == "success") {
                                    $('#sub-task-list').html(response.view);
                                }
                            }
                        });
                    }
                });
            });

            $('body').on('click', '.edit-subtask', function() {
                var id = $(this).data('row-id');
                var url = "{{ route('sub-tasks.edit', ':id') }}";
                url = url.replace(':id', id);
                $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
                $.ajaxModal(MODAL_LG, url);
            });

            $('body').on('change', '.task-check', function() {
                if ($(this).is(':checked')) {
                    var status = 'complete';
                } else {
                    var status = 'incomplete';
                }

                var id = $(this).data('sub-task-id');
                var url = "{{ route('sub_tasks.change_status') }}";
                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    url: url,
                    type: "POST",
                    data: {
                        '_token': token,
                        subTaskId: id,
                        status: status
                    },
                    success: function(response) {
                        if (response.status == "success") {

                            $('#sub-task-list').html(response.view);

                        }
                    }
                })
            });

            $('body').on('click', '.delete-file', function() {
                var id = $(this).data('row-id');
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('task-files.destroy', ':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {
                                '_token': token,
                                '_method': 'DELETE'
                            },
                            success: function(response) {
                                if (response.status == "success") {
                                    $('#task-file-list').html(response.view);
                                }
                            }
                        });
                    }
                });
            });

            $('body').on('click', '.delete-note', function() {
                var id = $(this).data('row-id');
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('task-note.destroy', ':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {
                                '_token': token,
                                '_method': 'DELETE'
                            },
                            success: function(response) {
                                if (response.status == "success") {
                                    $('#note-list').html(response.view);
                                }
                            }
                        });
                    }
                });
            });

            $('body').on('click', '.edit-note', function() {
                var id = $(this).data('row-id');
                var url = "{{ route('task-note.edit', ':id') }}";
                url = url.replace(':id', id);
                $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
                $.ajaxModal(MODAL_LG, url);
            });

            $('#start-task-timer').click(function() {
                var task_id = "{{ $task->id }}";
                var project_id = "{{ $task->project_id }}";
                var user_id = "{{ user()->id }}";
                var memo = "{{ $task->heading }}";
                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    url: "{{ route('timelogs.start_timer') }}",
                    blockUI: true,
                    type: "POST",
                    data: {
                        task_id: task_id,
                        project_id: project_id,
                        memo: memo,
                        '_token': token,
                        user_id: user_id
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            window.location.reload();
                        }
                    }
                })
            });

            $('body').on('click', '#reminderButton', function() {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.sendReminder')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmSend')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('tasks.reminder') }}";
                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            blockUI: true,
                            url: url,
                            data: {
                                'id': "{{ $task->id }}",
                                '_token': token
                            }
                        });
                    }
                });
            });

            $('body').on('click', '.comment-like', function() {
                var commentId = $(this).data('comment-id');
                var emojiName = $(this).data('emoji');
                var token = '{{ csrf_token() }}';
                const url = "{{ route('taskComment.save_comment_like') }}";

                $.easyAjax({
                    url: url,
                    type: "POST",
                    container: '#comment-list',
                    disableButton: true,
                    blockUI: true,
                    data: {
                        '_token': token,
                        'commentId': commentId,
                        'emojiName':emojiName
                    },
                    success: function(response) {
                        if (response.status == "success") {
                        $("#emoji-"+commentId).html(response.view);
                        }

                    }
                });
            });

            init(RIGHT_MODAL);

        $('#stop-task-timer').on('click', function() {
        var url = "{{ route('timelogs.stopper_alert', ':id') }}?via=timelog";
        var id = $(this).data('time-id');
        url = url.replace(':id', id);
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
        })

        });
    </script>
</div>
