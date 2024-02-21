<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ClientSubCategory
 *
 * @property int $id
 * @property int $category_id
 * @property string $category_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ClientCategory $clientCategory
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory whereUpdatedAt($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|ClientSubCategory whereCompanyId($value)
 * @mixin \Eloquent
 */
class ClientSubCategory extends BaseModel
{

    use HasCompany;

    protected $table = 'client_sub_categories';

    public function clientCategory(): BelongsTo
    {
        return $this->belongsTo(ClientCategory::class, 'category_id');
    }

}
