<?php

namespace App\Services;

use App\Models\TuitionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TuitionService
{
    /**
     * 指定月の月謝レコードを、その月に在籍している生徒ぶん用意して返す。
     * まだ行がない生徒は「未納」として作成する。
     *
     * ・入会日より前の月には作らない(入会日が空の生徒は在籍扱い)
     * ・自動で作るのは来月分まで(遠い未来の月を開いても行は増えない)
     */
    public function forMonth(User $user, string $yearMonth): Collection
    {
        $monthStart = Carbon::createFromFormat('Y-m-d', $yearMonth.'-01')->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $canCreate = $monthStart->lte(now()->startOfMonth()->addMonthNoOverflow());

        if ($canCreate) {
            $students = $user->students()->with('course')
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('enrolled_on')->orWhereDate('enrolled_on', '<=', $monthEnd))
                ->get();

            foreach ($students as $student) {
                TuitionPayment::firstOrCreate(
                    ['student_id' => $student->id, 'year_month' => $yearMonth],
                    ['amount' => $student->fee],
                );
            }
        }

        return TuitionPayment::with('student.course')
            ->where('year_month', $yearMonth)
            ->whereIn('student_id', $user->students()->select('id'))
            // 以前の作りで入会前の月にできてしまった未納の行は表示しない(入金済みの記録は残す)
            ->where(fn ($q) => $q->whereNotNull('paid_at')->orWhereHas('student', fn ($s) => $s
                ->whereNull('enrolled_on')->orWhereDate('enrolled_on', '<=', $monthEnd)))
            ->get()
            ->sortBy(fn ($p) => [$p->isPaid() ? 1 : 0, $p->student->kana ?? $p->student->name])
            ->values();
    }

    public function summary(Collection $payments): array
    {
        $total = $payments->sum('amount');
        $paid = $payments->filter->isPaid()->sum('amount');

        return [
            'count' => $payments->count(),
            'paid_count' => $payments->filter->isPaid()->count(),
            'total' => $total,
            'paid' => $paid,
            'unpaid' => $total - $paid,
            'rate' => $total > 0 ? (int) round($paid / $total * 100) : 0,
        ];
    }
}
