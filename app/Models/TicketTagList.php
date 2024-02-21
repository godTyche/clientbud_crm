<?php

namespace App\Models;

use App\Traits\HasCompany;

/**
 * App\Models\TicketTagList
 *
 * @property int $id
 * @property string $tag_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $icon
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList whereTagName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList whereUpdatedAt($value)
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTagList whereCompanyId($value)
 * @mixin \Eloquent
 */
class TicketTagList extends BaseModel
{

    use HasCompany;

    protected $table = 'ticket_tag_list';

    protected $guarded = ['id'];

}
