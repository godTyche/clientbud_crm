@php
$discussion = \App\Models\Discussion::find($notification->data['id']);
$projectId = $notification->data['project_id'];
$notificationUser = \App\Models\User::find($discussion->user_id);
$route = route('projects.show', $projectId) . '?tab=discussion';
@endphp

@if ($notificationUser)
    <x-cards.notification :notification="$notification"  :link="$route" :image="$notificationUser->image_url" :title="__('email.discussion.subject')"
        :text="$notification->data['title']" :time="$notification->created_at" />
@endif
