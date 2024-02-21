@php
    $active = false;

    if (isset($user->session)) {
        $lastSeen = \Carbon\Carbon::createFromTimestamp($user->session->last_activity)->timezone(company()?company()->timezone:$user->company->timezone);

        $lastSeenDifference = now()->diffInSeconds($lastSeen);
        if ($lastSeenDifference > 0 && $lastSeenDifference <= 90) {
            $active = true;
        }
    }
@endphp

<div class="media align-items-center mw-250">

    @if (!is_null($user))
        <a href="{{ isset($disabledLink) ? 'javascript:;' : route('employees.show', [$user->id]) }}"
           class="position-relative {{ isset($disabledLink) ? 'disabled-link' : '' }}">
            @if ($active)
                <span class="text-light-green position-absolute f-8 user-online"
                      title="@lang('modules.client.online')"><i class="fa fa-circle"></i></span>
            @endif
            <img src="{{ $user->image_url }}" class="mr-2 taskEmployeeImg rounded-circle"
                 alt="{{ $user->name }}" title="{{ $user->userBadge() }}">
        </a>
        <div class="media-body {{$user->status}}">

            <h5 class="mb-0 f-12">
                <a href="{{  isset($disabledLink) ? 'javascript:;' : route('employees.show', [$user->id]) }}"
                   class="text-darkest-grey {{ isset($disabledLink) ? 'disabled-link' : '' }}">{!!   $user->userBadge() !!}</a>
            </h5>
            <p class="mb-0 f-12 text-dark-grey">
                {{ !is_null($user->employeeDetail) && !is_null($user->employeeDetail->designation) ? $user->employeeDetail->designation->name : ' ' }}
            </p>
        </div>
    @else
        --
    @endif
</div>
