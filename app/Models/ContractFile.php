<?php

namespace App\Models;

use App\Traits\HasCompany;
use App\Traits\IconTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ContractFile
 *
 * @property int $id
 * @property int $user_id
 * @property int $contract_id
 * @property string $filename
 * @property string $hashname
 * @property string $size
 * @property string $google_url
 * @property string $dropbox_link
 * @property string $external_link_name
 * @property string $external_link
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $added_by
 * @property int|null $last_updated_by
 * @property-read \App\Models\Contract $contract
 * @property-read mixed $file_url
 * @property-read mixed $icon
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereDropboxLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereExternalLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereExternalLinkName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereGoogleUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereHashname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereUserId($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|ContractFile whereCompanyId($value)
 * @property-read mixed $file
 * @mixin \Eloquent
 */
class ContractFile extends BaseModel
{

    use IconTrait, HasCompany;

    const FILE_PATH = 'contract-files';
    protected $appends = ['file_url', 'icon', 'file'];

    public function getFileUrlAttribute()
    {
        if($this->external_link){
            return str($this->external_link)->contains('http') ? $this->external_link : asset_url_local_s3($this->external_link);
        }

        return asset_url_local_s3(ContractFile::FILE_PATH . '/' . $this->contract_id . '/' . $this->hashname);
    }

    public function getFileAttribute()
    {
        return $this->external_link ?: (ContractFile::FILE_PATH . '/' . $this->contract_id . '/' . $this->hashname);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

}
