<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // 絞り込みはセッションに覚えておき、他のページから戻っても消えないようにする
        if ($request->boolean('reset')) {
            $request->session()->forget('students.filters');

            return redirect()->route('students.index');
        }

        if ($request->hasAny(['q', 'status', 'course_id'])) {
            $filters = $request->only(['q', 'status', 'course_id']);
            $request->session()->put('students.filters', $filters);
        } else {
            $filters = $request->session()->get('students.filters', []);
        }

        $filters = array_merge(['q' => '', 'status' => 'active', 'course_id' => ''], array_filter($filters, fn ($v) => $v !== null));
        $isFiltered = $filters['q'] !== '' || $filters['status'] !== 'active' || $filters['course_id'] !== '';

        $query = $request->user()->students()->with('course');

        if ($q = trim($filters['q'])) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('kana', 'like', "%{$q}%")
                ->orWhere('guardian_name', 'like', "%{$q}%"));
        }
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['course_id']) {
            $query->where('course_id', $filters['course_id']);
        }

        $students = $query->orderBy('kana')->orderBy('name')->paginate(30);
        $courses = $request->user()->courses()->orderBy('weekday')->orderBy('start_time')->get();

        return view('students.index', compact('students', 'courses', 'filters', 'isFiltered'));
    }

    /** 一覧から在籍・休会・退会を切り替える */
    public function updateStatus(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeOwner($request, $student);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Student::STATUSES))],
        ]);

        $student->update($data);

        return back()->with('status', "{$student->name}さんを「{$student->status_label}」にしました。");
    }

    public function create(Request $request): View
    {
        return view('students.form', [
            'student' => new Student(['status' => 'active', 'enrolled_on' => now()]),
            'courses' => $request->user()->courses()->orderBy('weekday')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->students()->create($this->validated($request));

        return redirect()->route('students.index')->with('status', '生徒を登録しました。');
    }

    public function show(Request $request, Student $student): View
    {
        $this->authorizeOwner($request, $student);
        $student->load(['course', 'payments' => fn ($q) => $q->orderByDesc('year_month'), 'makeupLessons.makeupCourse']);

        // 入会前の月の未納行(以前の作りでできたもの)は履歴に出さない
        if ($student->enrolled_on) {
            $from = $student->enrolled_on->format('Y-m');
            $student->setRelation('payments', $student->payments
                ->filter(fn ($p) => $p->isPaid() || $p->year_month >= $from)->values());
        }

        return view('students.show', compact('student'));
    }

    public function edit(Request $request, Student $student): View
    {
        $this->authorizeOwner($request, $student);

        return view('students.form', [
            'student' => $student,
            'courses' => $request->user()->courses()->orderBy('weekday')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeOwner($request, $student);
        $student->update($this->validated($request));

        return redirect()->route('students.show', $student)->with('status', '生徒情報を更新しました。');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeOwner($request, $student);
        $student->delete();

        return redirect()->route('students.index')->with('status', '生徒を削除しました。');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'kana' => ['nullable', 'string', 'max:100'],
            'course_id' => ['nullable', Rule::exists('courses', 'id')->where('user_id', $request->user()->id)],
            'guardian_name' => ['nullable', 'string', 'max:100'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'monthly_fee' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'enrolled_on' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Student::STATUSES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        // コースの月謝と同じ金額なら「コースに合わせる」扱いにしておく
        // (あとでコースの月謝を変えたとき、この生徒にも反映されるように)
        if (! empty($data['course_id']) && isset($data['monthly_fee'])) {
            $courseFee = Course::whereKey($data['course_id'])->value('monthly_fee');
            if ((int) $data['monthly_fee'] === (int) $courseFee) {
                $data['monthly_fee'] = null;
            }
        }

        return $data;
    }

    private function authorizeOwner(Request $request, Student $student): void
    {
        abort_unless($student->user_id === $request->user()->id, 404);
    }
}
