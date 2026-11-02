@extends('layouts.app', ['title' => $course->exists ? 'コースを編集' : 'コースを追加'])

@section('content')
<h1 class="text-2xl font-bold mb-5">{{ $course->exists ? 'コースを編集' : 'コースを追加' }}</h1>

<form method="POST" action="{{ $course->exists ? route('courses.update', $course) : route('courses.store') }}" data-draft="course-{{ $course->id ?? 'new' }}" class="bg-white rounded-xl border border-stone-200 p-6 max-w-xl space-y-4">
    @csrf
    @if ($course->exists) @method('PUT') @endif

    <div data-tour="course-name"><x-field label="コース名" name="name">
        <input type="text" name="name" value="{{ old('name', $course->name) }}" required placeholder="例:キッズ初級" class="w-full rounded-lg border border-stone-300 px-3 py-2">
    </x-field></div>

    @php $checked = array_map('intval', old('weekdays', $course->days ?? [1])); @endphp
    <div data-tour="course-weekdays">
        <span class="block text-sm font-medium mb-1">曜日(複数選べます)</span>
        <div class="flex flex-wrap gap-2" id="weekday-boxes">
            @foreach (\App\Models\Course::WEEKDAY_ORDER as $i)
                <label class="cursor-pointer">
                    <input type="checkbox" name="weekdays[]" value="{{ $i }}" class="peer sr-only" @checked(in_array($i, $checked, true))>
                    <span class="inline-flex w-11 h-11 items-center justify-center rounded-lg border border-stone-300 bg-white text-sm
                                 peer-checked:bg-leaf-500 peer-checked:border-leaf-500 peer-checked:text-white peer-checked:font-bold
                                 peer-focus-visible:ring-2 peer-focus-visible:ring-leaf-500 {{ $i === 0 ? 'text-clay-600' : ($i === 6 ? 'text-sky-700' : '') }}">{{ \App\Models\Course::WEEKDAYS[$i] }}</span>
                </label>
            @endforeach
        </div>
        <span class="block text-xs text-stone-500 mt-1">2つ以上選ぶと、選んだ曜日すべてに通う「週2回」などのコースになります。曜日ごとに分けたいときは、別々に登録してください。</span>
        @error('weekdays')<span class="block text-xs text-clay-600 mt-1">{{ $message }}</span>@enderror
        @error('weekdays.*')<span class="block text-xs text-clay-600 mt-1">{{ $message }}</span>@enderror
    </div>

    <div class="grid grid-cols-2 gap-3">
        <x-field label="開始" name="start_time">
            <input type="time" name="start_time" value="{{ old('start_time', $course->start_time ? substr($course->start_time, 0, 5) : '16:00') }}" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
        <x-field label="終了" name="end_time">
            <input type="time" name="end_time" value="{{ old('end_time', $course->end_time ? substr($course->end_time, 0, 5) : '17:00') }}" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
    </div>

    <div data-tour="course-capacity" class="grid grid-cols-2 gap-3">
        <x-field label="定員(人)" name="capacity">
            <input type="number" name="capacity" value="{{ old('capacity', $course->capacity) }}" min="1" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
        <x-field label="月謝(円)" name="monthly_fee">
            <input type="number" name="monthly_fee" value="{{ old('monthly_fee', $course->monthly_fee) }}" min="0" step="100" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
    </div>

    <div class="flex items-center justify-between pt-2">
        <button class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white font-bold px-5 py-2">保存する</button>
        <a href="{{ route('courses.index') }}" data-discard class="text-sm text-stone-500">キャンセル</a>
    </div>
</form>

@if ($course->exists)
    <form method="POST" action="{{ route('courses.destroy', $course) }}" class="max-w-xl mt-4 text-right" onsubmit="return confirm('このコースを削除しますか?生徒のコース設定は空になります。')">
        @csrf @method('DELETE')
        <button class="text-sm text-clay-600">このコースを削除</button>
    </form>
@endif
@endsection
