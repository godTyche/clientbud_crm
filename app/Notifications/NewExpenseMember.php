<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\Expense;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class NewExpenseMember extends BaseNotification
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $expense;
    private $emailSetting;

    public function __construct(Expense $expense)
    {
        $this->expense = $expense;
        $this->company = $this->expense->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'new-expenseadded-by-member')->first();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['database'];

        if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting->status == 'active') {
            array_push($via, 'slack');
        }

        if ($this->emailSetting->send_push == 'yes') {
            array_push($via, OneSignalChannel::class);
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $build = parent::build();
        $url = route('expenses.show', $this->expense->id);
        $url = getDomainSpecificUrl($url, $this->company);

        if ($this->expense->status == 'approved') {
            $subject = __('email.newExpense.newSubject');
        }
        else {
            $subject = __('email.newExpense.subject');
        }

        $content = $subject . '<br>' . __('app.status') . ': ' . $this->expense->status . '<br>' . __('app.employee') . ': ' . $this->expense->user->name . '<br>' . __('modules.expenses.itemName') . ': ' . $this->expense->item_name . '<br>' . __('app.price') . ': ' . currency_format($this->expense->price, $this->expense->currency->id);

        return $build
            ->subject($subject . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.newExpense.action'),
                'notifiableName' => $notifiable->name
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
//phpcs:ignore
    public function toArray($notifiable)
    {
        return [
            'id' => $this->expense->id,
            'user_id' => $notifiable->id,
            'item_name' => $this->expense->item_name
        ];

    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     * @return SlackMessage
     */
    public function toSlack($notifiable)
    {
        $slack = $notifiable->company->slackSetting;

        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->image($slack->slack_logo_url)
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->content(__('email.newExpense.subject') . ' - ' . $this->expense->item_name . ' - ' . currency_format($this->expense->price, $this->expense->currency->id) . "\n" . url('/login'));
        }


    }

    // phpcs:ignore
    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject(__('email.newExpense.subject'))
            ->setBody($this->expense->item_name . ' by ' . $this->expense->user->name);
    }

}
