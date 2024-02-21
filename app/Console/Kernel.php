<?php

namespace App\Console;

use App\Console\Commands\AddMissingRolePermission;
use App\Console\Commands\AutoCreateRecurringExpenses;
use App\Console\Commands\AutoCreateRecurringInvoices;
use App\Console\Commands\AutoCreateRecurringTasks;
use App\Console\Commands\AutoStopTimer;
use App\Console\Commands\BirthdayReminderCommand;
use App\Console\Commands\CacheTest;
use App\Console\Commands\ClearNullSessions;
use App\Console\Commands\CreateTranslations;
use App\Console\Commands\FetchTicketEmails;
use App\Console\Commands\HideCronJobMessage;
use App\Console\Commands\RemoveSeenNotification;
use App\Console\Commands\SendAttendanceReminder;
use App\Console\Commands\SendAutoTaskReminder;
use App\Console\Commands\SendEventReminder;
use App\Console\Commands\SendAutoFollowUpReminder;
use App\Console\Commands\SendDailyTimelogReport;
use App\Console\Commands\SendProjectReminder;
use App\Console\Commands\UpdateExchangeRates;
use App\Console\Commands\SendInvoiceReminder;
use App\Console\Commands\SendMonthlyAttendanceReport;
use App\Console\Commands\SyncUserPermissions;
use App\Console\Commands\SendTimeTracker;
use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UpdateExchangeRates::class,
        AutoStopTimer::class,
        SendEventReminder::class,
        SendProjectReminder::class,
        HideCronJobMessage::class,
        SendAutoTaskReminder::class,
        CreateTranslations::class,
        AutoCreateRecurringInvoices::class,
        AutoCreateRecurringExpenses::class,
        ClearNullSessions::class,
        SendInvoiceReminder::class,
        RemoveSeenNotification::class,
        SendAttendanceReminder::class,
        AutoCreateRecurringTasks::class,
        SyncUserPermissions::class,
        SendAutoFollowUpReminder::class,
        FetchTicketEmails::class,
        AddMissingRolePermission::class,
        BirthdayReminderCommand::class,
        SendTimeTracker::class,
        SendMonthlyAttendanceReport::class,
        SendDailyTimelogReport::class,
        CacheTest::class
    ];

    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): DateTimeZone|string|null
    {
        // Get the timezone from the configuration
        return config('app.cron_timezone');
    }


    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {



        $schedule->command('recurring-task-create')->dailyAt('23:59');
        $schedule->command('auto-stop-timer')->dailyAt('23:30');
        $schedule->command('birthday-notification')->dailyAt('09:00');

        // Every Minute
        $schedule->command('send-event-reminder')->everyMinute();
        $schedule->command('hide-cron-message')->everyMinute();
        $schedule->command('send-attendance-reminder')->everyMinute();
        $schedule->command('sync-user-permissions')->everyMinute();
        // $schedule->command('fetch-ticket-emails')->everyMinute(); // phpcs:ignore
        $schedule->command('send-auto-followup-reminder')->everyMinute();
        $schedule->command('send-time-tracker')->everyMinute();

        // Daily
        $schedule->command('send-project-reminder')->daily();
        $schedule->command('send-auto-task-reminder')->daily();
        $schedule->command('recurring-invoice-create')->daily();
        $schedule->command('recurring-expenses-create')->daily();
        $schedule->command('send-invoice-reminder')->daily();
        $schedule->command('delete-seen-notification')->daily();
        $schedule->command('update-exchange-rate')->daily();
        $schedule->command('send-daily-timelog-report')->daily();
        $schedule->command('log:clear --keep-last')->daily();

        // Hourly
        $schedule->command('clear-null-session')->hourly();
        $schedule->command('create-database-backup')->hourly();
        $schedule->command('delete-database-backup')->hourly();
        $schedule->command('add-missing-permissions')->everyThirtyMinutes();

        $schedule->command('send-monthly-attendance-report')->monthlyOn();

        $schedule->command('queue:flush')->weekly();

        // Schedule the queue:work command to run without overlapping and with 3 tries
        $schedule->command('queue:work database --tries=3 --stop-when-empty')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }

}
