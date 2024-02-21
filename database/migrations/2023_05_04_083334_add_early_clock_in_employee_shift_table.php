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

        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn('early_clock_in');
        });

        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->string('early_clock_in')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->dropColumn('early_clock_in');
        });

        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->string('early_clock_in')->nullable();
        });
    }

};
