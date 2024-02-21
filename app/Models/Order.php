<?php

namespace App\Models;

use App\Http\Requests\UnitTypeRequest;
use App\Scopes\ActiveScope;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int|null $client_id
 * @property string $order_date
 * @property float $sub_total
 * @property float $total
 * @property float $due_amount
 * @property string $status
 * @property int|null $currency_id
 * @property string $show_shipping_address
 * @property string|null $note
 * @property int|null $added_by
 * @property int|null $last_updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $client
 * @property-read \App\Models\ClientDetails|null $clientdetails
 * @property-read \App\Models\Currency|null $currency
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderItems[] $items
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $invoice
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Payment[] $payment
 * @property-read int|null $payment_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $recurrings
 * @property-read int|null $recurrings_count
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDueAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShowShippingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @property mixed $order_number
 * @property float $discount
 * @property string $discount_type
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscountType($value)
 * @property int|null $company_id
 * @property int|null $company_address_id
 * @property-read \App\Models\CompanyAddress|null $address
 * @property-read \App\Models\Company|null $company
 * @property int|null $unit_id
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCompanyAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderNumber($value)
 * @property-read \App\Models\UnitType $unit
 * @property int|null $unit_id
 * @property string|null $custom_order_number
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomOrderNumber($value)
 * @property string|null $original_order_number
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOriginalOrderNumber($value)
 * @mixin \Eloquent
 */
class Order extends BaseModel
{

    use HasCompany;

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id')->withoutGlobalScope(ActiveScope::class);
    }

    public function clientdetails(): BelongsTo
    {
        return $this->belongsTo(ClientDetails::class, 'client_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItems::class, 'order_id');
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id')->orderBy('paid_on', 'desc');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CompanyAddress::class, 'company_address_id');
    }

    public static function lastOrderNumber()
    {
        return (int)Order::latest()->first()?->original_order_number ?? 0;
    }

    /*
    public function getOrderNumberAttribute()
    {
        return Str::upper(__('app.order')) . '#' .$this->attributes['order_number'];
    }
    */

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'unit_id');
    }

    public function formatOrderNumber()
    {
        $orderSettings = (company()) ? company()->invoiceSetting : $this->company->invoiceSetting;
        return \App\Helper\NumberFormat::order($this->order_number, $orderSettings);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

}
