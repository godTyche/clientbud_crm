<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {

        if (!Schema::hasColumn('attendances', 'overwrite_attendance')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->enum('overwrite_attendance', ['yes', 'no'])->default('no');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'overwrite_attendance')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn(['overwrite_attendance']);
            });
        }

    }
    
};
