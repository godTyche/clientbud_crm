<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EmailMarketing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'addedBy'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addedBy');
    }
}
