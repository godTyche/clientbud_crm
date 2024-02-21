<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ContractSign
 *
 * @property int $id
 * @property int $contract_id
 * @property string $full_name
 * @property string $email
 * @property string $signature
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Contract $contract
 * @property-read mixed $icon
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign query()
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereUpdatedAt($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereCompanyId($value)
 * @property string|null $place
 * @property string|null $date
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractSign wherePlace($value)
 * @mixin \Eloquent
 */
class ContractSign extends BaseModel
{

    use HasCompany;

    protected $casts = [
        'date' => 'date',
    ];

    public function getSignatureAttribute()
    {
        return asset_url_local_s3('contract/sign/' . $this->attributes['signature']);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

}
