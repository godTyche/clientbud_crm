<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\EmployeeLeaveQuota;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeavesQuotaController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.leaves';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('leaves', $this->user->modules));
            return $next($request);
        });
    }

    public function update(Request $request, $id)
    {
        if ($request->leaves < 0) {
            return Reply::error('messages.leaveTypeValueError');
        }

        $type = EmployeeLeaveQuota::findOrFail($id);
        $type->no_of_leaves = $request->leaves;
        $type->save();

        session()->forget('user');

        return Reply::success(__('messages.leaveTypeAdded'));
    }

    public function employeeLeaveTypes($userId)
    {
        if ($userId != 0) {
            $leaveQuotas = LeaveType::select('leave_types.*', 'employee_details.notice_period_start_date', 'employee_details.probation_end_date',
            'employee_details.department_id as employee_department', 'employee_details.designation_id as employee_designation',
            'employee_details.marital_status as maritalStatus', 'users.gender as usergender', 'employee_details.joining_date', 'employee_leave_quotas.no_of_leaves as employeeLeave')
                ->join('employee_leave_quotas', 'employee_leave_quotas.leave_type_id', 'leave_types.id')
                ->join('users', 'users.id', 'employee_leave_quotas.user_id')
                ->join('employee_details', 'employee_details.user_id', 'users.id')
                ->where('users.id', $userId)->get();

            $roles = User::with('roles')->findOrFail($userId);

            $userRole = [];
            $userRoles = $roles->roles->count() > 1 ? $roles->roles->where('name', '!=', 'employee') : $roles->roles;

            foreach($userRoles as $role){
                $userRole[] = $role->id;
            }

            $options = '';

            foreach($leaveQuotas as $leave){
                $leaveType = LeaveType::leaveTypeCodition($leave, $userRole);

                if ($leave->employeeLeave > 0) { /** @phpstan-ignore-line */
                    if($leaveType){
                        $options .= '<option value="' . $leave->id . '"> ' .  $leave->type_name . ' </option>'; /** @phpstan-ignore-line */

                    }
                }
            }
        }
        else {
            $leaveQuotas = LeaveType::all();

            $options = '';

            foreach ($leaveQuotas as $leaveQuota) {
                $options .= '<option value="' . $leaveQuota->id . '"> ' .  $leaveQuota->type_name . ' </option>';
            }
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }

}
