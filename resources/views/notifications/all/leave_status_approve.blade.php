@php
$notificationUser = \App\Models\User::find($notification->data['user_id']);
@endphp

@if ($notificationUser)
    <x-cards.notification :notification="$notification"  :link="route('leaves.show', $notification->data['id'])" :image="$notificationUser->image_url"
        :title="__('email.leave.approve')"
        :text="\Carbon\Carbon::parse($notification->data['leave_date'])->translatedFormat(company()->date_format)"
        :time="$notification->created_at" />
@endif
