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
        Schema::table('mention_users', function (Blueprint $table) {
            $table->integer('user_chat_id')->unsigned()->nullable()->after('event_id');
            $table->foreign('user_chat_id')->references('id')->on('users_chat')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mention_users', function (Blueprint $table) {
            //
        });
    }

};
