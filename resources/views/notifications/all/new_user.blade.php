<x-cards.notification :notification="$notification"  link="javascript:;" :image="company()->logo_url"
    :title="__('app.welcome') . ' ' . __('app.to') . ' ' . $companyName . ' !'" :time="$notification->created_at" />
