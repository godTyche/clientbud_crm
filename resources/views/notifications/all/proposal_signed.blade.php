@if ($notification->data['status'] == 'accepted')
    <x-cards.notification :notification="$notification" :link="route('proposals.show', $notification->data['id'])"
        :image="company()->logo_url" :title="__('email.proposalSigned.subject')"
        :text="__('app.menu.proposal').'#'.$notification->data['id']" :time="$notification->created_at" />
@else
    <x-cards.notification :notification="$notification" :link="route('proposals.show', $notification->data['id'])"
        :image="company()->logo_url" :title="__('email.proposalRejected.subject')"
        :text="__('app.menu.proposal').'#'.$notification->data['id']" :time="$notification->created_at" />
@endif
