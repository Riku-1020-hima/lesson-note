<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    public const STATUSES = [
        'active' => '在籍',
        'paused' => '休会',
        'withdrawn' => '退会',
    ];

    protected $fillable = [
        'course_id', 'name', 'kana', 'guardian_name', 'guardian_email', 'guardian_phone',
        'monthly_fee', 'enrolled_on', 'status', 'note',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_on' => 'date',
            'monthly_fee' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TuitionPayment::class);
    }

    public function makeupLessons(): HasMany
    {
        return $this->hasMany(MakeupLesson::class);
    }

    /** 実際に請求する月謝(個別設定がなければコースの月謝) */
    public function getFeeAttribute(): int
    {
        return $this->monthly_fee ?? $this->course?->monthly_fee ?? 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
