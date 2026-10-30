<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MakeupLesson extends Model
{
    public const STATUSES = [
        'pending' => '振替待ち',
        'booked' => '予約済み',
        'done' => '完了',
        'cancelled' => '取消',
    ];

    protected $fillable = ['student_id', 'absent_date', 'reason', 'makeup_course_id', 'makeup_date', 'status'];

    protected function casts(): array
    {
        return [
            'absent_date' => 'date',
            'makeup_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function makeupCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'makeup_course_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
