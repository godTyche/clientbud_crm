<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\LeadPipeline;
use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportDealJob implements ShouldQueue
{

    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($row, $columns, $company = null)
    {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if (
            $this->isColumnExists('email') &&
            $this->isColumnExists('name') &&
            $this->isColumnExists('pipeline') &&
            $this->isColumnExists('stages') &&
            $this->isColumnExists('value') &&
            $this->isColumnExists('close_date')
        ) {

            $lead = Lead::withoutGlobalScopes()->where('client_email', $this->getColumnValue('email'))->where('company_id', $this->company?->id)->first();

            if (!$lead) {
                $this->failJob(__('messages.invalidData'));
                return;
            }

            $pipeline = LeadPipeline::withoutGlobalScopes()->where('name', $this->getColumnValue('pipeline'))->where('company_id', $this->company?->id)->first();

            if (!$pipeline) {
                $pipeline = LeadPipeline::withoutGlobalScopes()->where('company_id', $this->company?->id)->first();
            }

            if (!$pipeline) {
                $this->failJob(__('messages.invalidData'));
                return;
            }

            $stage = $pipeline->stages->where('name', $this->getColumnValue('stages'))->first();

            if (!$stage) {
                $stage = $pipeline->stages->where('default', 1)->first();
            }

            if (!$stage) {
                $this->failJob(__('messages.invalidData'));
                return;
            }

            DB::beginTransaction();
            try {

                $deal = new Deal();
                $deal->name = $this->getColumnValue('name');
                $deal->lead_id = $lead->id;
                $deal->next_follow_up = 'yes';
                $deal->lead_pipeline_id = $pipeline->id;
                $deal->pipeline_stage_id = $stage ->id;
                $deal->close_date = Carbon::parse($this->getColumnValue('close_date'))->format('Y-m-d');
                $deal->value = ($this->getColumnValue('value')) ?: 0;
                $deal->currency_id = $this->company->currency_id;
                $deal->save();

                // Log search
                $this->logSearchEntry($deal->id, $deal->name, 'deals.show', 'deal');

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->failJobWithMessage($e->getMessage());
            }
        }
        else {
            $this->failJob(__('messages.invalidData'));
        }
    }

}

