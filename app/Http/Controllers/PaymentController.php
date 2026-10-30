<?php

namespace App\Http\Controllers;

use App\Models\TuitionPayment;
use App\Services\TuitionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request, TuitionService $tuition): View
    {
        // 表示中の月を覚えておき、他のページから戻っても同じ月を開く
        $month = $request->input('month', $request->session()->get('payments.month', now()->format('Y-m')));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = now()->format('Y-m');
        }
        $request->session()->put('payments.month', $month);

        $payments = $tuition->forMonth($request->user(), $month);
        $summary = $tuition->summary($payments);

        $current = Carbon::createFromFormat('Y-m-d', $month.'-01');

        return view('payments.index', [
            'payments' => $payments,
            'summary' => $summary,
            'month' => $month,
            'monthLabel' => $current->format('Y年n月'),
            'prev' => $current->copy()->subMonth()->format('Y-m'),
            'next' => $current->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * 月謝の記録をCSVで書き出す(Excelでそのまま開けるように BOM 付き UTF-8)
     * ?month=2026-10 でその月、?year=2026 でその年の1年分
     */
    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $query = TuitionPayment::with('student.course')
            ->whereIn('student_id', $request->user()->students()->select('id'));

        if (! empty($data['year'])) {
            $query->where('year_month', 'like', $data['year'].'-%');
            $label = $data['year'].'年';
        } else {
            $month = $data['month'] ?? now()->format('Y-m');
            $query->where('year_month', $month);
            $label = Carbon::createFromFormat('Y-m-d', $month.'-01')->format('Y年n月');
        }

        // 画面と同じく、入会前の月にできた未納の行は出さない
        $payments = $query->get()
            ->filter(fn ($p) => $p->isPaid() || ! $p->student->enrolled_on || $p->year_month >= $p->student->enrolled_on->format('Y-m'))
            ->sortBy(fn ($p) => [$p->year_month, $p->student->kana ?? $p->student->name])
            ->values();

        $filename = "月謝_{$label}.csv";

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel で文字化けしないように
            fputcsv($out, ['年月', '生徒名', 'ふりがな', 'コース', '金額', '状態', '入金日', '支払い方法'], ',', '"', '');
            foreach ($payments as $p) {
                fputcsv($out, [
                    $p->year_month,
                    $p->student->name,
                    $p->student->kana,
                    $p->student->course?->name,
                    $p->amount,
                    $p->isPaid() ? '入金済' : '未納',
                    $p->paid_at?->format('Y-m-d'),
                    $p->method,
                ], ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function toggle(Request $request, TuitionPayment $payment): RedirectResponse
    {
        abort_unless($payment->student->user_id === $request->user()->id, 404);

        if ($payment->isPaid()) {
            $payment->update(['paid_at' => null, 'method' => null]);
        } else {
            $payment->update([
                'paid_at' => now(),
                'method' => $request->input('method', '現金'),
            ]);
        }

        return back()->with('status', $payment->student->name.'さんの入金状況を更新しました。');
    }
}
