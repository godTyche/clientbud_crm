<?php

namespace App\Observers;

use App\Enums\MaritalStatus;
use Illuminate\Support\Carbon;
use App\Models\EmployeeDetails;
use App\Models\EmployeeLeaveQuota;

class EmployeeDetailsObserver
{

    public function saving(EmployeeDetails $detail)
    {
        if (!isRunningInConsoleOrSeeding() && auth()->check()) {
            $detail->last_updated_by = user()->id;
        }
    }

    public function creating(EmployeeDetails $detail)
    {
        if (!isRunningInConsoleOrSeeding() && auth()->check()) {
            $detail->added_by = user()->id;
        }

        $detail->company_id = $detail->user->company_id;

        if (is_null($detail->marital_status)) {
            $detail->marital_status = MaritalStatus::Single;
        }

    }

    public function created(EmployeeDetails $detail)
    {
        $leaveTypes = $detail->company->leaveTypes;
        $settings = company();
        $countOfMonthsAllowed = 12;

        if ($settings && $settings->leaves_start_from == 'year_start')
        {
            $joiningDate = $detail->joining_date->copy()->addDay()->startOfMonth();
            $startingDate = Carbon::create($joiningDate->year + 1, $settings->year_starts_from)->startOfMonth();
            $differenceMonth = $joiningDate->diffInMonths($startingDate);
            $countOfMonthsAllowed = $differenceMonth > 12 ? $differenceMonth - 12 : $differenceMonth;
        }

        foreach ($leaveTypes as $value) {
            $leaves = ($settings && $settings->leaves_start_from == 'year_start') ? floor($value->no_of_leaves / 12 * $countOfMonthsAllowed) : $value->no_of_leaves;

            EmployeeLeaveQuota::create(
                [
                    'user_id' => $detail->user_id,
                    'leave_type_id' => $value->id,
                    'no_of_leaves' => $leaves,
                ]
            );
        }
    }

}
