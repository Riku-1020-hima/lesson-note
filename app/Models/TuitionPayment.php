<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TuitionPayment extends Model
{
    protected $fillable = ['student_id', 'year_month', 'amount', 'paid_at', 'method'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
