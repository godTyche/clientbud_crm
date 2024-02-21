<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\LeadAgent;
use App\Models\Lead;
use App\Models\LeadPipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($companyId)
    {

        $leadAgents = User::select('users.id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', 'employee')
            ->where('users.company_id', $companyId)
            ->inRandomOrder()
            ->take(3)->get();

        $agents = [];

        foreach ($leadAgents as $agent) {
            array_push($agents, ['user_id' => $agent->id, 'company_id' => $companyId]);
        }

        LeadAgent::insert($agents);

        $currencyID = Currency::where('company_id', $companyId)->first()->id;

        $randomLeadId = LeadAgent::where('company_id', $companyId)->inRandomOrder()->first()->id;

        $randomPipelineId = LeadPipeline::where('company_id', $companyId)->inRandomOrder()->first()->id;
        $randomStageId = PipelineStage::where('company_id', $companyId)->where('lead_pipeline_id', $randomPipelineId)->inRandomOrder()->first()->id;

        $leadContact = new Lead();
        $leadContact->company_id = $companyId;
        $leadContact->website = 'https://worksuite.biz';
        $leadContact->address = 'Jaipur, India';
        $leadContact->client_name = 'John Doe';
        $leadContact->client_email = 'testing@test.com';
        $leadContact->mobile = '123456789';
        $leadContact->note = 'Quas consectetur, tempor incidunt, aliquid voluptatem, velit mollit et illum, adipisicing ea officia aliquam placeat';
        $leadContact->save();

        $lead = new \App\Models\Deal();
        $lead->lead_id = $leadContact->id;
        $lead->lead_pipeline_id = $randomPipelineId;
        $lead->pipeline_stage_id = $randomStageId;
        $lead->company_id = $companyId;
        $lead->agent_id = $randomLeadId;
        $lead->name = 'Test Lead';
        $lead->value = rand(10000, 99999);
        $lead->currency_id = $currencyID;
        $lead->next_follow_up = 'yes';
        $lead->note = 'Quas consectetur, tempor incidunt, aliquid voluptatem, velit mollit et illum, adipisicing ea officia aliquam placeat';
        $lead->save();

    }

}
