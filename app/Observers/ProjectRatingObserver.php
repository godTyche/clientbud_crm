<?php

namespace App\Observers;

use App\Events\RatingEvent;
use App\Models\Notification;
use App\Models\ProjectRating;

class ProjectRatingObserver
{

    public function created(ProjectRating $rating)
    {
        if (!isRunningInConsoleOrSeeding()) {
            // Send notification to user
            event(new RatingEvent($rating, 'add'));
        }
    }

    public function deleting(ProjectRating $rating)
    {
        if (!isRunningInConsoleOrSeeding()) {
            // Send notification to user
            event(new RatingEvent($rating, 'update'));

        }

        $notifyData = ['App\Notifications\RatingUpdate'];
        \App\Models\Notification::deleteNotification($notifyData, $rating->id);

    }

}
