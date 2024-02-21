<?php

namespace App\Models;

use App\Traits\HasCompany;

/**
 * App\Models\OfflinePaymentMethod
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $icon
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod whereUpdatedAt($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OfflinePaymentMethod active()
 * @mixin \Eloquent
 */
class OfflinePaymentMethod extends BaseModel
{

    use HasCompany;

    protected $table = 'offline_payment_methods';
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function activeMethod()
    {
        return OfflinePaymentMethod::where('status', 'yes')->get();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'yes');
    }

}
