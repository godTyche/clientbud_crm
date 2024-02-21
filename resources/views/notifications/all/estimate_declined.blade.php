@php
if ($notification->data['estimate_number'] == '') {
    $estimate = \App\Estimate::find($notification->data['id']);
    $estimateNumber = $estimate->estimate_number;
} else {
    $estimateNumber = $notification->data['estimate_number'];
}
@endphp

<x-cards.notification :notification="$notification"  :link="route('estimates.show', $notification->data['id'])" :image="company()->logo_url"
    :title="__('email.estimateDeclined.subject')" :text="$estimateNumber" :time="$notification->created_at" />
