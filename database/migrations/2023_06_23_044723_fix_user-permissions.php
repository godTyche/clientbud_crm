<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::withoutGlobalScopes()->where('customised_permissions', 0)->update(['permission_sync' => 0]);

        Artisan::call('sync-user-permissions', ['all' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

};
