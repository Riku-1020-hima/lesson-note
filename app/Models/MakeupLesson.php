<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    /** 期限が近いとみなす日数 */
    public const SOON_DAYS = 7;

    /** 振替の期限日(期限なしの教室なら null) */
    public function deadline(?int $days): ?CarbonInterface
    {
        return $days ? $this->absent_date->copy()->addDays($days)->endOfDay() : null;
    }

    /**
     * 振替待ちの期限の状態
     * ['date' => 期限日, 'left' => 残り日数, 'level' => ok / soon / expired]
     * 期限なし、または振替待ちでないときは null
     */
    public function deadlineInfo(?int $days): ?array
    {
        $deadline = $this->deadline($days);
        if (! $deadline || $this->status !== 'pending') {
            return null;
        }

        $left = (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        return [
            'date' => $deadline,
            'left' => $left,
            'level' => $left < 0 ? 'expired' : ($left <= self::SOON_DAYS ? 'soon' : 'ok'),
        ];
    }
}
