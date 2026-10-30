<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    public const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

    /** 画面で並べる順(月曜はじまり) */
    public const WEEKDAY_ORDER = [1, 2, 3, 4, 5, 6, 0];

    protected $fillable = ['name', 'weekdays', 'start_time', 'end_time', 'capacity', 'monthly_fee'];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'weekdays' => 'array',
            'capacity' => 'integer',
            'monthly_fee' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // 曜日は月曜はじまりの順に並べ、weekday には最初の曜日を入れておく(並び替え用)
        static::saving(function (Course $course) {
            $days = collect($course->weekdays ?? [$course->weekday ?? 1])
                ->map(fn ($d) => (int) $d)
                ->unique()
                ->sortBy(fn ($d) => array_search($d, self::WEEKDAY_ORDER, true))
                ->values()
                ->all();

            $course->weekdays = $days;
            $course->weekday = $days[0];
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function makeupLessons(): HasMany
    {
        return $this->hasMany(MakeupLesson::class, 'makeup_course_id');
    }

    /** 例: [1, 4] */
    public function getDaysAttribute(): array
    {
        return $this->weekdays ?: [$this->weekday];
    }

    /** 例: 「月・木」 */
    public function getWeekdayLabelAttribute(): string
    {
        return collect($this->days)->map(fn ($d) => self::WEEKDAYS[$d] ?? '')->implode('・');
    }

    /** 例: 「月・木曜 16:00〜16:45」 */
    public function getScheduleLabelAttribute(): string
    {
        return sprintf('%s曜 %s〜%s', $this->weekday_label, substr($this->start_time, 0, 5), substr($this->end_time, 0, 5));
    }

    public function getTimesPerWeekAttribute(): int
    {
        return count($this->days);
    }

    public function meetsOn(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeek, $this->days, true);
    }

    /**
     * その日の参加人数
     * = 在籍中の生徒 − その日に欠席する生徒 + 振替で来る生徒
     */
    public function attendeeCountOn(CarbonInterface $date): int
    {
        $day = $date->toDateString();

        $regular = $this->students()->where('status', 'active')->count();

        // 欠席の連絡があった生徒の席は空く(取り消した欠席は数えない)
        $absent = MakeupLesson::whereDate('absent_date', $day)
            ->where('status', '!=', 'cancelled')
            ->whereHas('student', fn ($q) => $q->where('course_id', $this->id)->where('status', 'active'))
            ->distinct('student_id')
            ->count('student_id');

        $makeups = $this->makeupLessons()
            ->whereDate('makeup_date', $day)
            ->whereIn('status', ['booked', 'done'])
            ->count();

        return max(0, $regular - $absent) + $makeups;
    }

    public function hasSeatOn(CarbonInterface $date): bool
    {
        return $this->meetsOn($date) && $this->attendeeCountOn($date) < $this->capacity;
    }
}
