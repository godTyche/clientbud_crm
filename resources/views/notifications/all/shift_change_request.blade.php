@php
$notificationUser = \App\Models\User::find($notification->data['user_id']);
$shift = \App\Models\EmployeeShift::find($notification->data['new_shift_id']);
@endphp

@if ($notificationUser && $shift)
<x-cards.notification :notification="$notification"  :link="route('shifts-change.index')"
    :image="$notificationUser->image_url" :title="__('email.shiftChange.subject') . ' - '.\Carbon\Carbon::parse($notification->data['date'])->translatedFormat(company()->date_format)"
    :text="__('modules.attendance.shiftName').': '.$shift->shift_name" :time="$notification->created_at" />
@endif
