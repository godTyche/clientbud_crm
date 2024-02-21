@component('mail::message')
# @lang('email.taskReminder.subject')

@lang('email.reminder.subject')

<h5>@lang('app.taskDetails')</h5>

@component('mail::text', ['text' => $content])

@endcomponent

@component('mail::button', ['url' => $url, 'themeColor' => $themeColor])
@lang('app.viewTask')
@endcomponent

@lang('email.regards'),<br>
{{ config('app.name') }}
@endcomponent
