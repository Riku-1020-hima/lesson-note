<?php

namespace App\Http\Controllers;

use App\Models\MakeupLesson;
use App\Models\Student;
use App\Models\TuitionPayment;
use App\Services\MessageGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function create(Request $request): View
    {
        return view('messages.create', [
            'students' => $request->user()->students()->where('status', 'active')->orderBy('kana')->get(),
            'types' => MessageGenerator::TYPES,
            'input' => [
                'type' => $request->input('type', 'unpaid'),
                'student_id' => $request->input('student_id'),
                'extra' => '',
            ],
            'result' => null,
            'aiEnabled' => (bool) config('services.anthropic.key'),
        ]);
    }

    public function generate(Request $request, MessageGenerator $generator): View
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(MessageGenerator::TYPES))],
            'student_id' => ['required', Rule::exists('students', 'id')->where('user_id', $request->user()->id)],
            'extra' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::with('course')->findOrFail($data['student_id']);
        $context = $this->context($data['type'], $student);

        $result = $generator->generate(
            $data['type'],
            $student,
            $request->user()->name,
            $data['extra'] ?? '',
            $context,
        );

        return view('messages.create', [
            'students' => $request->user()->students()->where('status', 'active')->orderBy('kana')->get(),
            'types' => MessageGenerator::TYPES,
            'input' => $data + ['extra' => ''],
            'result' => $result,
            'aiEnabled' => (bool) config('services.anthropic.key'),
        ]);
    }

    /** 実データから金額や日付を渡す(AIに数字を作らせないため) */
    private function context(string $type, Student $student): array
    {
        if ($type === 'unpaid') {
            $payment = TuitionPayment::where('student_id', $student->id)
                ->whereNull('paid_at')
                ->orderByDesc('year_month')
                ->first();

            $month = $payment ? Carbon::createFromFormat('Y-m-d', $payment->year_month.'-01') : now();

            return [
                'month_label' => $month->format('n月'),
                'amount' => $payment?->amount ?? $student->fee,
            ];
        }

        if ($type === 'makeup') {
            $makeup = MakeupLesson::with('makeupCourse')
                ->where('student_id', $student->id)
                ->whereIn('status', ['booked', 'pending'])
                ->orderByDesc('created_at')
                ->first();

            if ($makeup && $makeup->status === 'booked' && $makeup->makeupCourse) {
                $w = ['日', '月', '火', '水', '木', '金', '土'][$makeup->makeup_date->dayOfWeek];

                return ['makeup_label' => sprintf(
                    '%sのお休み分の振替は、%s(%s) %s〜 の「%s」クラスでご用意しております。',
                    $makeup->absent_date->format('n月j日'),
                    $makeup->makeup_date->format('n月j日'),
                    $w,
                    substr($makeup->makeupCourse->start_time, 0, 5),
                    $makeup->makeupCourse->name,
                )];
            }
        }

        return [];
    }
}
