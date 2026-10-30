<?php

namespace App\Http\Controllers;

use App\Models\TuitionPayment;
use App\Services\TuitionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
