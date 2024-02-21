<?php

use App\Models\EmailNotificationSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        EmailNotificationSetting::where('slug', 'clock-in-notification')->delete();
    }

};
