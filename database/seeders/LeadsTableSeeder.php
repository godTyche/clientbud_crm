<?php
namespace Database\Seeders;

use App\Models\Deal;
use App\Models\LeadAgent;
use App\Models\Lead;
use App\Models\LeadPipeline;
use App\Models\PipelineStage;
use App\Models\LeadStatus;
use Illuminate\Database\Seeder;
use App\Traits\UniversalSearchTrait;

class LeadsTableSeeder extends Seeder
{
    use UniversalSearchTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($companyId)
    {
        $count = config('app.seed_record_count');
        $faker = \Faker\Factory::create();
        $leadAgents = $this->getLeadAgent($companyId);
        $getPipeline = $this->getPipeline($companyId);
        $getLeadStage = $this->getLeadStage($companyId);
        $getLeadContact = $this->getContact($companyId);


        Deal::factory()
            ->count((int)$count)
            ->make()
            ->each(function (Deal $lead) use($companyId, $faker, $leadAgents, $getPipeline, $getLeadStage, $getLeadContact) {
                $lead->company_id = $companyId;
                $lead->agent_id = $faker->randomElement($leadAgents); /* @phpstan-ignore-line */
                $lead->lead_pipeline_id = $faker->randomElement($getPipeline); /* @phpstan-ignore-line */
                $lead->pipeline_stage_id = $faker->randomElement($getLeadStage); /* @phpstan-ignore-line */
                $lead->lead_id = $faker->randomElement($getLeadContact); /* @phpstan-ignore-line */
                $lead->save();
            });
    }

    private function getLeadAgent($companyId)
    {
        return LeadAgent::where('company_id', $companyId)->pluck('id')->toArray();
    }

    private function getLeadStatus($companyId)
    {
        return LeadStatus::where('company_id', $companyId)->pluck('id')->toArray();
    }

    private function getPipeline($companyId)
    {
        return LeadPipeline::where('company_id', $companyId)->pluck('id')->toArray();
    }

    private function getLeadStage($companyId)
    {
        $leadPipeline = LeadPipeline::where('company_id', $companyId)->first();
        return PipelineStage::where('company_id', $companyId)->where('lead_pipeline_id', $leadPipeline->id)->pluck('id')->toArray();
    }

    private function getContact($companyId)
    {
        return Lead::where('company_id', $companyId)->pluck('id')->toArray();
    }

}
