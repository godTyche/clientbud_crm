@component('mail::message')
# @lang('email.hello')@if(!empty($notifiableName)){{ ' '.$notifiableName }}@endif!

@lang('email.newTask.subject')

# @lang('app.taskDetails')

@component('mail::text', ['text' => $content])

@endcomponent

@component('mail::button', ['url' => $url, 'themeColor' => $themeColor])
@lang('app.viewTask')
@endcomponent

@lang('email.regards'),<br>
{{ config('app.name') }}
@endcomponent
