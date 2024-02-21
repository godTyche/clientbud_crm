<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use App\Traits\CustomFieldsTrait;
use App\Traits\HasCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * App\Models\Invoice
 *
 * @property int $id
 * @property int|null $project_id
 * @property int|null $quickbooks_invoice_id
 * @property int|null $client_id
 * @property string $invoice_number
 * @property string $project_name
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property float $sub_total
 * @property float $discount
 * @property string $discount_type
 * @property float $total
 * @property int|null $currency_id
 * @property int|null $default_currency_id
 * @property double|null $exchange_rate
 * @property string $status
 * @property string $recurring
 * @property int|null $billing_cycle
 * @property int|null $billing_interval
 * @property string|null $billing_frequency
 * @property string|null $file
 * @property string|null $file_original_name
 * @property string|null $note
 * @property int $credit_note
 * @property string $show_shipping_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $estimate_id
 * @property int $send_status
 * @property float $due_amount
 * @property float $amount
 * @property int|null $parent_id
 * @property int|null $invoice_recurring_id
 * @property int|null $created_by
 * @property int|null $added_by
 * @property int|null $last_updated_by
 * @property-read \App\Models\User|null $client
 * @property-read \App\Models\ClientDetails|null $clientdetails
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\CreditNotes[] $creditNotes
 * @property-read int|null $credit_notes_count
 * @property-read \App\Models\Currency|null $currency
 * @property-read \App\Models\Estimate|null $estimate
 * @property-read mixed $extras
 * @property-read mixed $icon
 * @property-read mixed $issue_on
 * @property-read mixed $total_amount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\InvoiceItems[] $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Payment[] $payment
 * @property-read int|null $payment_count
 * @property-read \App\Models\Project|null $project
 * @property-read \Illuminate\Database\Eloquent\Collection|Invoice[] $recurrings
 * @property-read int|null $recurrings_count
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice pending()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereBillingCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereBillingFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereBillingInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCreditNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereDueAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereEstimateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereFileOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereInvoiceRecurringId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereSendStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereShowShippingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereUpdatedAt($value)
 * @property int|null $order_id
 * @property string|null $hash
 * @property-read \App\Models\Order|null $order
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereOrderId($value)
 * @property string $calculate_tax
 * @property int|null $company_address_id
 * @property-read \App\Models\CompanyAddress|null $address
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCalculateTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCompanyAddressId($value)
 * @property string|null $event_id
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereEventId($value)
 * @property int|null $company_id
 * @property string|null $custom_invoice_number
 * @property-read \App\Models\Company|null $company
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCustomInvoiceNumber($value)
 * @property int|null $bank_account_id
 * @property \Illuminate\Support\Carbon|null $last_viewed
 * @property string|null $ip_address
 * @property-read \App\Models\UnitType|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereDefaultCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereLastViewed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereQuickbooksInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereUnitId($value)
 * @property string $payment_status
 * @property string|null $downloadable_file
 * @property string|null $default_image
 * @property int|null $offline_method_id
 * @property string|null $transaction_id
 * @property string|null $gateway
 * @property-read \App\Models\BankAccount|null $bankAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceFiles> $files
 * @property-read int|null $files_count
 * @property-read mixed $download_file_url
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereGateway($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereOfflineMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereTransactionId($value)
 * @property string|null $original_invoice_number
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereOriginalInvoiceNumber($value)
 * @mixin \Eloquent
 */
class Invoice extends BaseModel
{

    use Notifiable;
    use CustomFieldsTrait;
    use HasCompany;

    protected $casts = [
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
        'last_viewed' => 'datetime',
    ];
    protected $appends = ['total_amount', 'issue_on'];
    protected $with = ['currency', 'address'];

    const CUSTOM_FIELD_MODEL = 'App\Models\Invoice';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id')->withoutGlobalScope(ActiveScope::class);
    }

    public function clientdetails(): BelongsTo
    {
        return $this->belongsTo(ClientDetails::class, 'client_id', 'user_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNotes::class);
    }

    public function recurrings(): HasMany
    {
        return $this->hasMany(Invoice::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItems::class, 'invoice_id');
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id')->orderBy('paid_on', 'desc');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'estimate_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CompanyAddress::class, 'company_address_id');
    }

    public function bankAccount(): belongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function scopePending($query)
    {
        return $query->where(function ($q) {
            $q->where('invoices.status', 'unpaid')
                ->orWhere('invoices.status', 'partial');
        });
    }

    public static function clientInvoices($clientId)
    {
        return Invoice::join('projects', 'projects.id', '=', 'invoices.project_id')
            ->select('projects.project_name', 'invoices.*')
            ->where('projects.client_id', $clientId)
            ->get();
    }

    public static function lastInvoiceNumber()
    {
        return (int)Invoice::latest()->first()?->original_invoice_number ?? 0;
    }

    public function appliedCredits()
    {
        return Payment::where('invoice_id', $this->id)->sum('amount');
    }

    public function amountDue()
    {
        $due = $this->total - ($this->amountPaid());

        return $due < 0 ? 0 : $due;
    }

    public function amountPaid()
    {
        return $this->payment->where('status', 'complete')->sum('amount');
    }

    public function getPaidAmount()
    {
        return $this->payment->sum('amount');
    }

    public function getTotalAmountAttribute()
    {

        if (!is_null($this->total) && !is_null($this->currency->currency_symbol)) {
            return $this->currency->currency_symbol . $this->total;
        }

        return '';
    }

    public function getIssueOnAttribute()
    {
        if (is_null($this->issue_date)) {
            return '';
        }

        return Carbon::parse($this->issue_date)->format('d F, Y');

    }

    public function formatInvoiceNumber()
    {
        $invoiceSettings = company() ? company()->invoiceSetting : $this->company->invoiceSetting;
        return \App\Helper\NumberFormat::invoice($this->invoice_number, $invoiceSettings);
    }

    public function getDownloadFileUrlAttribute()
    {
        return ($this->downloadable_file) ? asset_url_local_s3(InvoiceFiles::FILE_PATH . '/' . $this->downloadable_file) : null;
    }

    public function files(): HasMany
    {
        return $this->hasMany(InvoiceFiles::class, 'invoice_id')->orderBy('id', 'desc');
    }

}
