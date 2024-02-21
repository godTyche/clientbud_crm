<?php

namespace App\Models;

use App\Traits\HasCompany;

/**
 * App\Models\ProjectSetting
 *
 * @property int $id
 * @property string $send_reminder
 * @property int $remind_time
 * @property string $remind_type
 * @property string $remind_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $icon
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereRemindTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereRemindTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereRemindType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereSendReminder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereUpdatedAt($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereCompanyId($value)
 * @mixin \Eloquent
 */
class ProjectSetting extends BaseModel
{

    use HasCompany;

    const REMIND_TO_MEMBERS = 'members';
    const REMIND_TO_ADMINS = 'admins';

    public function getRemindToAttribute($value)
    {
        return json_decode($value);
    }

    public function setRemindToAttribute($value)
    {
        $this->attributes['remind_to'] = json_encode($value);
    }

}
