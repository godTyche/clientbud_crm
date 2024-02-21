<?php

namespace App\Observers;

use App\Models\Product;
use App\Traits\UnitTypeSaveTrait;

class ProductObserver
{

    use UnitTypeSaveTrait;

    public function saving(Product $product)
    {
        $this->unitType($product);

        if (!isRunningInConsoleOrSeeding()) {
            $product->last_updated_by = user() ? user()->id : null;
        }
    }

    public function creating(Product $product)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $product->added_by = user() ? user()->id : null;
        }

        if (company()) {
            $product->company_id = company()->id;
        }
    }

    public function deleting(Product $product)
    {
        $product->files()->each(function ($file) {
            $file->delete();
        });
    }

}
