@if (in_array('week_timelog', $activeWidgets) && $sidebarUserPermissions['view_timelogs'] != 5 && $sidebarUserPermissions['view_timelogs'] != 'none' && in_array('timelogs', user_modules()))
    <div @class(['mb-3', 'col-md-6' => (in_array('lead', $activeWidgets) && $leadAgent), 'col-md-12' => !(in_array('lead', $activeWidgets) && $leadAgent)])>
        <div
            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
            <div class="d-block text-capitalize w-100">
                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">@lang('modules.dashboard.weekTimelog') <span class="badge badge-secondary ml-1 f-10">{{ minute_to_hour($weekWiseTimelogs - $weekWiseTimelogBreak) . ' ' . __('modules.timeLogs.thisWeek') }}</span></h5>

                <div id="weekly-timelogs">
                    <nav class="mb-3">
                        <ul class="pagination pagination-sm week-pagination">
                            @foreach ($weekPeriod->toArray() as $date)
                                <li
                                    @class([
                                        'page-item',
                                        'week-timelog-day',
                                        'active' => (now(company()->timezone)->toDateString() == $date->toDateString()),
                                    ])
                                    data-toggle="tooltip" data-original-title="{{ $date->translatedFormat(company()->date_format) }}" data-date="{{ $date->toDateString() }}">
                                    <a class="page-link" href="javascript:;">{{ $date->isoFormat('dd') }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                    <div class="progress" style="height: 7px;">
                        @php
                            $totalDayMinutes = $dateWiseTimelogs->sum('total_minutes');
                            $totalDayBreakMinutes = $dateWiseTimelogBreak->sum('total_minutes');
                            $totalDayMinutesPercent = ($totalDayMinutes > 0) ? floatval((floatval($totalDayMinutes - $totalDayBreakMinutes)/$totalDayMinutes) * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $totalDayMinutesPercent }}%" aria-valuenow="{{ $totalDayMinutesPercent }}" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-original-title="{{ minute_to_hour($totalDayMinutes - $totalDayBreakMinutes) }}"></div>

                        <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ (100 - $totalDayMinutesPercent) }}%" aria-valuenow="{{ $totalDayMinutesPercent }}" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-original-title="{{ minute_to_hour($totalDayBreakMinutes) }}"></div>
                    </div>

                    <div class="d-flex justify-content-between mt-1 text-dark-grey f-12">
                        <small>@lang('app.duration'): {{ minute_to_hour($dateWiseTimelogs->sum('total_minutes') - $dateWiseTimelogBreak->sum('total_minutes')) }}</small>
                        <small>@lang('modules.timeLogs.break'): {{ minute_to_hour($dateWiseTimelogBreak->sum('total_minutes')) }}</small>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endif
