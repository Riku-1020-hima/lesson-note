<?php

namespace App\Http\Controllers;

use App\Models\MakeupLesson;
use App\Services\TuitionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TuitionService $tuition): View
    {
        $user = $request->user();
        $month = now()->format('Y-m');

        $payments = $tuition->forMonth($user, $month);
        $summary = $tuition->summary($payments);
        $unpaid = $payments->reject->isPaid()->take(8);

        $todayCourses = $user->courses()
            ->orderBy('start_time')
            ->get()
            ->filter(fn ($c) => $c->meetsOn(now()))
            ->values();

        $pendingMakeups = MakeupLesson::with('student')
            ->whereIn('student_id', $user->students()->select('id'))
            ->where('status', 'pending')
            ->orderBy('absent_date')
            ->get();

        $activeCount = $user->students()->where('status', 'active')->count();

        // はじめにやること(全部終わったら表示しない)
        $setup = [
            ['done' => $user->courses()->exists(), 'title' => 'コースを追加する', 'text' => '授業の曜日・時間・定員・月謝を登録します', 'url' => route('courses.create')],
            ['done' => $user->students()->exists(), 'title' => '生徒を登録する', 'text' => 'コースを選ぶと月謝が自動で入ります', 'url' => route('students.create')],
            ['done' => \App\Models\TuitionPayment::whereIn('student_id', $user->students()->select('id'))->whereNotNull('paid_at')->exists(),
                'title' => '月謝の入金を記録する', 'text' => '月謝表で「入金済みにする」を押してみましょう', 'url' => route('payments.index')],
            ['done' => $user->app_name !== null || $user->logo_path !== null, 'title' => '教室名やアイコンを整える(任意)', 'text' => '画面左上の名前やロゴを変えられます', 'url' => route('settings.edit'), 'optional' => true],
        ];
        $setupRequiredDone = collect($setup)->reject(fn ($s) => $s['optional'] ?? false)->every('done');

        return view('dashboard', compact('summary', 'unpaid', 'todayCourses', 'pendingMakeups', 'activeCount', 'month', 'setup', 'setupRequiredDone'));
    }
}
