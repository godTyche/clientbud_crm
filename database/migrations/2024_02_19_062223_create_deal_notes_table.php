<?php

use App\Models\Company;
use App\Models\DashboardWidget;
use App\Models\Deal;
use App\Models\DealFile;
use App\Models\DealFollowUp;
use App\Models\Lead;
use App\Models\LeadPipeline;
use App\Models\LeadProduct;
use App\Models\PipelineStage;
use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionType;
use App\Models\Proposal;
use App\Models\PurposeConsentLead;
use App\Models\RoleUser;
use App\Models\UserLeadboardSetting;
use App\Models\UserPermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('deal_notes')) {
            Schema::create('deal_notes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->longText('details')->nullable();
                $table->unsignedBigInteger('deal_id')->nullable();
                $table->unsignedInteger('added_by')->nullable();
                $table->unsignedInteger('last_updated_by')->nullable();
                $table->foreign('last_updated_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('added_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade')->onUpdate('cascade');
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_notes');
    }

};
