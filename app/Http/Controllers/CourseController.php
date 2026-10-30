<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = $request->user()->courses()
            ->withCount(['students' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('weekday')->orderBy('start_time')
            ->get();

        return view('courses.index', compact('courses'));
    }

    public function create(): View
    {
        return view('courses.form', ['course' => new Course(['capacity' => 8, 'weekdays' => [1]])]);
    }

    public function store(Request $request): RedirectResponse
    {
        // 複数の曜日を選んだら「週に複数回通う1つのコース」になる
        $request->user()->courses()->create($this->validated($request));

        return redirect()->route('courses.index')->with('status', 'コースを登録しました。');
    }

    public function edit(Request $request, Course $course): View
    {
        $this->authorizeOwner($request, $course);

        return view('courses.form', compact('course'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);
        $course->update($this->validated($request));

        return redirect()->route('courses.index')->with('status', 'コースを更新しました。');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);
        $course->delete();

        return redirect()->route('courses.index')->with('status', 'コースを削除しました。');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'weekdays' => ['required', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['integer', 'between:0,6', 'distinct'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'monthly_fee' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [
            'weekdays.required' => '曜日を1つ以上選んでください。',
        ]);

        $data['weekdays'] = array_map('intval', $data['weekdays']);

        return $data;
    }

    private function authorizeOwner(Request $request, Course $course): void
    {
        abort_unless($course->user_id === $request->user()->id, 404);
    }
}
