<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MakeupLesson;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MakeupLessonController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', $request->session()->get('makeups.status', 'open'));
        if (! in_array($status, ['open', 'done', 'cancelled', 'all'], true)) {
            $status = 'open';
        }
        $request->session()->put('makeups.status', $status);

        $query = MakeupLesson::with(['student.course', 'makeupCourse'])
            ->whereIn('student_id', $request->user()->students()->select('id'));

        if ($status === 'open') {
            $query->whereIn('status', ['pending', 'booked']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $makeups = $query->orderByRaw("case status when 'pending' then 0 when 'booked' then 1 else 2 end")
            ->orderBy('absent_date')
            ->get();

        $deadlineDays = $request->user()->makeup_deadline_days;

        return view('makeups.index', compact('makeups', 'status', 'deadlineDays'));
    }

    public function create(Request $request): View
    {
        return view('makeups.create', [
            'students' => $request->user()->students()->where('status', 'active')->orderBy('kana')->get(),
            'selected' => $request->input('student_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('user_id', $request->user()->id)],
            'absent_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        $makeup = MakeupLesson::create($data + ['status' => 'pending']);

        return redirect()->route('makeups.edit', $makeup)->with('status', '欠席を登録しました。続けて振替日を選べます。');
    }

    /** 振替先を選ぶ画面。今後4週間で空きのある枠を出す */
    public function edit(Request $request, MakeupLesson $makeup): View
    {
        $this->authorizeOwner($request, $makeup);
        $makeup->load('student.course');

        $courses = $request->user()->courses()->orderBy('weekday')->orderBy('start_time')->get();
        $slots = collect();
        $start = now()->startOfDay();
        $deadlineDays = $request->user()->makeup_deadline_days;
        $deadline = $makeup->deadline($deadlineDays);

        for ($i = 0; $i < 28; $i++) {
            $date = $start->copy()->addDays($i);
            if ($deadline && $date->gt($deadline)) {
                break; // 期限より後の枠は出さない
            }
            foreach ($courses->filter(fn ($c) => $c->meetsOn($date)) as $course) {
                $count = $course->attendeeCountOn($date);
                $slots->push([
                    'course' => $course,
                    'date' => $date,
                    'count' => $count,
                    'available' => $count < $course->capacity,
                ]);
            }
        }

        return view('makeups.edit', compact('makeup', 'slots', 'deadline', 'deadlineDays'));
    }

    public function update(Request $request, MakeupLesson $makeup): RedirectResponse
    {
        $this->authorizeOwner($request, $makeup);

        $action = $request->input('action', 'book');

        if ($action === 'done' || $action === 'cancel') {
            $makeup->update(['status' => $action === 'done' ? 'done' : 'cancelled']);

            return redirect()->route('makeups.index')->with('status', '振替の状態を更新しました。');
        }

        $data = $request->validate([
            'makeup_course_id' => ['required', Rule::exists('courses', 'id')->where('user_id', $request->user()->id)],
            'makeup_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $course = Course::findOrFail($data['makeup_course_id']);
        $date = Carbon::parse($data['makeup_date']);

        $deadline = $makeup->deadline($request->user()->makeup_deadline_days);
        if ($deadline && $date->gt($deadline)) {
            throw ValidationException::withMessages([
                'makeup_date' => '振替の期限('.$deadline->format('n月j日').')を過ぎた日は選べません。期限は「設定」で変えられます。',
            ]);
        }

        if (! $course->hasSeatOn($date)) {
            throw ValidationException::withMessages([
                'makeup_date' => 'その日はこのコースの曜日ではないか、定員に空きがありません。',
            ]);
        }

        $makeup->update($data + ['status' => 'booked']);

        return redirect()->route('makeups.index')->with('status', $makeup->student->name.'さんの振替を予約しました。');
    }

    private function authorizeOwner(Request $request, MakeupLesson $makeup): void
    {
        abort_unless($makeup->student->user_id === $request->user()->id, 404);
    }
}
