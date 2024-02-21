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
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->enum('visible', ['true', 'false'])->default('false')->after('export')->nullable();
        });

        \App\Models\EmployeeShift::where('shift_name', 'Day Off')->update(['color' => '#E8EEF3']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumn('visible');
        });
    }

};
