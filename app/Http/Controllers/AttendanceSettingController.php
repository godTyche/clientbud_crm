<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\AttendanceSetting\UpdateAttendanceSetting;
use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\EmployeeShift;
use App\Models\Holiday;
use App\Models\Role;
use Carbon\Carbon;

class AttendanceSettingController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.attendanceSettings';
        $this->activeSettingMenu = 'attendance_settings';
        $this->middleware(function ($request, $next) {
            abort_403(!(user()->permission('manage_attendance_setting') == 'all' && in_array('attendance', user_modules())));

            return $next($request);
        });
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $this->ipAddresses = [];
        $this->attendanceSetting = AttendanceSetting::first();
        $this->monthlyReportRoles = json_decode($this->attendanceSetting->monthly_report_roles);
        $this->roles = Role::where('name', '<>', 'client')->get();

        if (json_decode($this->attendanceSetting->ip_address)) {
            $this->ipAddresses = json_decode($this->attendanceSetting->ip_address, true);
        }

        $tab = request('tab');

        switch ($tab) {
        case 'shift':
            $this->weekMap = Holiday::weekMap();
            $this->employeeShifts = EmployeeShift::where('shift_name', '<>', 'Day Off')->get();
            $this->view = 'attendance-settings.ajax.shift';
            break;
        default:
            $this->view = 'attendance-settings.ajax.attendance';
            break;
        }

        $this->activeTab = $tab ?: 'attendance';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle, 'activeTab' => $this->activeTab]);
        }

        return view('attendance-settings.index', $this->data);
    }

    /**
     * @param UpdateAttendanceSetting $request
     * @param int $id
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    //phpcs:ignore
    public function update(UpdateAttendanceSetting $request, $id)
    {
        $setting = company()->attendanceSetting;
        $setting->employee_clock_in_out = ($request->employee_clock_in_out == 'yes') ? 'yes' : 'no';
        $setting->radius_check = ($request->radius_check == 'yes') ? 'yes' : 'no';
        $setting->ip_check = ($request->ip_check == 'yes') ? 'yes' : 'no';
        $setting->radius = $request->radius;
        $setting->ip_address = json_encode($request->ip);
        $setting->alert_after = $request->alert_after;
        $setting->week_start_from = $request->week_start_from;
        $setting->alert_after_status = ($request->alert_after_status == 'on') ? 1 : 0;
        $setting->save_current_location = ($request->save_current_location) ? 1 : 0;
        $setting->allow_shift_change = ($request->allow_shift_change) ? 1 : 0;
        $setting->auto_clock_in = ($request->auto_clock_in) ? 'yes' : 'no';
        $setting->show_clock_in_button = ($request->show_clock_in_button == 'yes') ? 'yes' : 'no';
        $setting->auto_clock_in_location = $request->auto_clock_in_location;
        $setting->monthly_report = ($request->monthly_report) ? 1 : 0;
        $setting->monthly_report_roles = json_encode($request->monthly_report_roles);
        $setting->save();

        session()->forget(['attendance_setting','company']);

        return Reply::success(__('messages.updateSuccess'));
    }

}
