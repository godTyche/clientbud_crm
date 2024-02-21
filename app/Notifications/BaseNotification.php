<?php

namespace App\Notifications;

use App\Models\GlobalSetting;
use App\Models\SmtpSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class BaseNotification extends Notification implements ShouldQueue
{

    use Queueable, Dispatchable;

    protected $company = null;
    protected $slack = null;

    /**
     * Create a new notification instance.
     *
     * @return MailMessage
     */
    public function build()
    {
        // Set the company in every Notification class
        $company = $this->company;
        $globalSetting = GlobalSetting::first();

        if (!is_null($company)) {
            App::setLocale($company->locale ?? 'en');
        }
        else {
            App::setLocale(session('locale') ?: $globalSetting->locale);
        }

        $smtpSetting = SmtpSetting::first();

        $build = (new MailMessage);

        $replyName = $companyName = $smtpSetting->mail_from_name;
        $replyEmail = $companyEmail = $smtpSetting->mail_from_email;

        Config::set('app.logo', $globalSetting->masked_logo_url);

        if (isWorksuite()) {
            return $build->from($companyEmail, $companyName);
        }

        if (!is_null($company)) {
            $replyName = $company->company_name;
            $replyEmail = $company->company_email;
            Config::set('app.logo', $company->masked_logo_url);
            Config::set('app.name', $replyName);
        }

        $companyEmail = config('mail.verified') === true ? $companyEmail : $replyEmail;
        $companyName = config('mail.verified') === true ? $companyName : $replyName;

        return $build->from($companyEmail, $companyName)->replyTo($replyEmail, $replyName);
    }

    protected function modifyUrl($url)
    {
        return getDomainSpecificUrl($url, $this->company);
    }

    protected function slackRedirectMessage($subjectKey, $notifiable)
    {
        $slack = $notifiable->company->slackSetting;

        return (new SlackMessage())
            ->from(config('app.name'))
            ->image(asset_url_local_s3('slack-logo/' . $slack->slack_logo))
            ->content('*' . __($subjectKey) . '*' . "\n" . 'This is a redirected notification. Add slack username for *' . $notifiable->name . '*');
    }

}
