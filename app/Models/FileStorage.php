<?php

namespace App\Models;

use App\Traits\HasCompany;
use App\Traits\IconTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\FileStorage
 *
 * @property int $id
 * @property string $path
 * @property string $filename
 * @property string|null $type
 * @property int $size
 * @property string $storage_location
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereStorageLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereUpdatedAt($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @property-read mixed $file_url
 * @property-read mixed $icon
 * @property-read mixed $size_format
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileStorage whereFilename($value)
 * @mixin \Eloquent
 */
class FileStorage extends BaseModel
{

    use HasFactory, IconTrait, HasCompany;

    protected $table = 'file_storage';

    protected $appends = ['file_url', 'icon', 'size_format'];

    public function getFileUrlAttribute()
    {
        return asset_url_local_s3($this->path . '/' . $this->filename);
    }

    public function getSizeFormatAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        if ($bytes > 1) {
            return $bytes . ' bytes';
        }

        if ($bytes == 1) {
            return $bytes . ' byte';
        }

        return '0 bytes';

    }

}
