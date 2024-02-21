<?php

namespace App\Models;

use App\Traits\HasCompany;
use App\Traits\IconTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\UserchatFile
 *
 * @property int $id
 * @property int $user_id
 * @property int $users_chat_id
 * @property string $filename
 * @property string|null $description
 * @property string|null $google_url
 * @property string|null $hashname
 * @property string|null $size
 * @property string|null $external_link
 * @property string|null $external_link_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserChat $chat
 * @property-read mixed $file_url
 * @property-read mixed $icon
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereExternalLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereExternalLinkName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereGoogleUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereHashname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereUsersChatId($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|UserchatFile whereCompanyId($value)
 * @property-read mixed $file
 * @mixin \Eloquent
 */
class UserchatFile extends BaseModel
{

    use IconTrait;
    use HasCompany;

    const FILE_PATH = 'message-files';

    protected $appends = ['file_url', 'icon', 'file'];
    protected $table = 'users_chat_files';

    public function getFileUrlAttribute()
    {
        if($this->external_link){
            return str($this->external_link)->contains('http') ? $this->external_link : asset_url_local_s3($this->external_link);
        }

        return asset_url_local_s3(UserchatFile::FILE_PATH . '/' . $this->hashname);
    }

    public function getFileAttribute()
    {
        return $this->external_link ?: (UserchatFile::FILE_PATH . '/' . $this->hashname);
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(UserChat::class, 'users_chat_id');
    }

}
