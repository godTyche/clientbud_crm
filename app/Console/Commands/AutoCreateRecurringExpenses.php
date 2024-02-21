<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseRecurring;
use Illuminate\Console\Command;

class AutoCreateRecurringExpenses extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-expenses-create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'auto create recurring expenses ';

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
            $this->createRecurringExpenses($company);
        }
    }

    private function createRecurringExpenses($company)
    {
        $recurringExpenses = ExpenseRecurring::with('recurrings')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->get();

        foreach ($recurringExpenses as $recurring) {

            if (is_null($recurring->next_expense_date)) {
                continue;
            }

            $totalExistingCount = $recurring->recurrings->count();

            if ($recurring->unlimited_recurring == 1 || ($totalExistingCount < $recurring->billing_cycle)) {

                if ($recurring->next_expense_date->timezone($company->timezone)->isToday()) {
                    $this->makeExpense($recurring);
                    $this->saveNextInvoiceDate($recurring);
                }
            }
        }
    }

    private function makeExpense($recurring)
    {
        $expense = new Expense();
        $expense->company_id = $recurring->company_id;
        $expense->expenses_recurring_id = $recurring->id;
        $expense->category_id = $recurring->category_id;
        $expense->project_id = $recurring->project_id;
        $expense->currency_id = $recurring->currency_id;
        $expense->user_id = $recurring->user_id;
        $expense->created_by = $recurring->created_by;
        $expense->item_name = $recurring->item_name;
        $expense->description = $recurring->description;
        $expense->price = $recurring->price;
        $expense->purchase_from = $recurring->purchase_from;
        $expense->added_by = $recurring->added_by;
        $expense->bank_account_id = $recurring->bank_account_id;
        $expense->purchase_date = now()->format('Y-m-d');
        $expense->status = 'approved';
        $expense->save();
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

        $recurring->next_expense_date = $days->format('Y-m-d');
        $recurring->save();
    }

}
