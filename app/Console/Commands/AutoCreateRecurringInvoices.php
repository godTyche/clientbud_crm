<?php

namespace App\Console\Commands;

use App\Helper\Files;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Invoice;
use App\Models\InvoiceItemImage;
use App\Models\InvoiceItems;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItemImage;
use App\Models\UnitType;
use App\Models\User;
use App\Notifications\NewInvoiceRecurring;
use App\Scopes\ActiveScope;
use App\Traits\UniversalSearchTrait;
use Illuminate\Console\Command;

class AutoCreateRecurringInvoices extends Command
{

    use UniversalSearchTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-invoice-create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'auto create recurring invoices ';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $companies = Company::select('id', 'timezone')->get();

        foreach ($companies as $company) {

            $this->info('Running for company:' . $company->id);

            $recurringInvoices = RecurringInvoice::with(['recurrings'])
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->get();

            foreach ($recurringInvoices as $recurring) {


                if (is_null($recurring->next_invoice_date)) {
                    continue;
                }

                $totalExistingCount = $recurring->recurrings->count();

                if ($recurring->unlimited_recurring == 1 || ($totalExistingCount < $recurring->billing_cycle)) {

                    if ($recurring->next_invoice_date->timezone($company->timezone)->isToday()) {
                        $this->invoiceCreate($recurring);
                        $this->saveNextInvoiceDate($recurring);
                    }
                }
            }

        }
    }

    private function saveNextInvoiceDate($recurring)
    {
        $days = match ($recurring->rotation) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'bi-weekly' => now()->addWeeks(2),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addQuarter(),
            'half-yearly' => now()->addMonths(6),
            'annually' => now()->addYear(),
            default => now()->addDay(),
        };

        $recurring->next_invoice_date = $days->format('Y-m-d');
        $recurring->save();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function invoiceCreate($invoiceData)
    {

        $recurring = $invoiceData;

        $defaultAddress = CompanyAddress::where('is_default', 1)->where('company_id', $recurring->company_id)->first();

        $diff = $recurring->issue_date->diffInDays($recurring->due_date);
        $dueDate = now()->addDays($diff)->format('Y-m-d');

        $unitType = UnitType::where('default', 1)->where('company_id', $recurring->company_id)->first();

        $invoice = new Invoice();
        $invoice->invoice_recurring_id = $recurring->id;
        $invoice->company_id = $recurring->company_id;
        $invoice->project_id = $recurring->project_id ?? null;
        $invoice->client_id = $recurring->client_id ?: null;
        $invoice->invoice_number = Invoice::lastInvoiceNumber() + 1;
        $invoice->issue_date = now()->format('Y-m-d');
        $invoice->due_date = $dueDate;
        $invoice->sub_total = round($recurring->sub_total, 2);
        $invoice->discount = $recurring->discount;
        $invoice->discount_type = $recurring->discount_type;
        $invoice->total = round($recurring->total, 2);
        $invoice->currency_id = $recurring->currency_id;
        $invoice->note = $recurring->note;
        $invoice->show_shipping_address = $recurring->show_shipping_address;
        $invoice->send_status = 1;
        $invoice->company_address_id = $defaultAddress->id;
        $invoice->bank_account_id = $recurring->bank_account_id;
        $invoice->save();

        if ($invoice->show_shipping_address) {
            if ($invoice->project_id != null && $invoice->project_id != '') {
                $client = $invoice->project->clientdetails;
                $client->shipping_address = $invoice->project->client->clientDetails->shipping_address;
                $client->save();
            }
            elseif ($invoice->client_id != null && $invoice->client_id != '') {
                $client = $invoice->clientdetails;
                $client->shipping_address = $invoice->client->clientDetails->shipping_address;
                $client->save();
            }

        }

        foreach ($recurring->items as $item) {

            $invoiceItem = InvoiceItems::create(
                [
                    'invoice_id' => $invoice->id,
                    'item_name' => $item->item_name,
                    'item_summary' => $item->item_summary,
                    'unit_id' => (!is_null($item->unit_id)) ? $item->unit_id : null,
                    'product_id' => (!is_null($item->product_id)) ? $item->product_id : null,
                    'hsn_sac_code' => (isset($item->hsn_sac_code)) ? $item->hsn_sac_code : null,
                    'type' => 'item',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                    'taxes' => $item->taxes
                ]
            );

            if ($item->recurringInvoiceItemImage) {
                // Add invoice item image
                InvoiceItemImage::create(
                    [
                        'invoice_item_id' => $invoiceItem->id,
                        'filename' => $item->recurringInvoiceItemImage->filename,
                        'hashname' => $item->recurringInvoiceItemImage->hashname,
                        'size' => $item->recurringInvoiceItemImage->size,
                        'external_link' => $item->recurringInvoiceItemImage->external_link
                    ]
                );

                // Copy files here
                if ($item->recurringInvoiceItemImage->filename != '') {

                    $source = public_path(Files::UPLOAD_FOLDER . '/') . RecurringInvoiceItemImage::FILE_PATH . '/' . $item->id . '/' . $item->recurringInvoiceItemImage->hashname;

                    $path = public_path(Files::UPLOAD_FOLDER . '/') . InvoiceItemImage::FILE_PATH . '/' . $invoiceItem->id . '/';

                    $filename = $item->recurringInvoiceItemImage->hashname;

                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }

                    copy($source, $path . $filename);
                }
            }

        }


        if (($invoice->project && $invoice->project->client_id != null) || $invoice->client_id != null) {
            $clientId = ($invoice->project && $invoice->project->client_id != null) ? $invoice->project->client_id : $invoice->client_id;
            // Notify client
            $notifyUser = User::withoutGlobalScope(ActiveScope::class)->find($clientId);

            if ($notifyUser) {
                $notifyUser->notify(new NewInvoiceRecurring($invoice));
            }
        }

        // Log search
        $this->logSearchEntry($invoice->id, $invoice->invoice_number, 'invoices.show', 'invoice');
    }

}
