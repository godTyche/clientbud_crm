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
        Schema::create('resource_centers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('icon');
            $table->string('colour');
            $table->integer('addedBy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_centers');
    }
};
