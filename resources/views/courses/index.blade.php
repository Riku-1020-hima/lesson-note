@extends('layouts.app', ['title' => 'コース'])

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold">コース</h1>
    <a data-tour="course-add" href="{{ route('courses.create') }}" class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white text-sm font-bold px-4 py-2">+ コースを追加</a>
</div>

<div data-tour="course-list" class="bg-white rounded-xl border border-stone-200 overflow-x-auto">
    <table class="rtable w-full text-sm">
        <thead class="bg-stone-50 text-stone-500 text-left">
            <tr>
                <th class="px-4 py-2.5 font-medium">コース名</th>
                <th class="px-4 py-2.5 font-medium">曜日・時間</th>
                <th class="px-4 py-2.5 font-medium text-right">在籍/定員</th>
                <th class="px-4 py-2.5 font-medium text-right">月謝</th>
                <th class="px-4 py-2.5"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($courses as $c)
            <tr class="border-t border-stone-100">
                <td class="rt-main px-4 py-3 font-medium">{{ $c->name }}</td>
                <td data-label="曜日・時間" class="px-4 py-3">{{ $c->schedule_label }}
                    @if ($c->times_per_week > 1)<span class="ml-1 text-xs rounded-full bg-leaf-50 text-leaf-700 px-2 py-0.5">週{{ $c->times_per_week }}回</span>@endif
                </td>
                <td data-label="在籍/定員" class="px-4 py-3 text-right {{ $c->students_count >= $c->capacity ? 'text-clay-600 font-bold' : '' }}">{{ $c->students_count }}/{{ $c->capacity }}</td>
                <td data-label="月謝" class="px-4 py-3 text-right">{{ number_format($c->monthly_fee) }}円</td>
                <td class="rt-actions px-4 py-3 text-right"><a href="{{ route('courses.edit', $c) }}" class="text-leaf-600">編集</a></td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state message="まだコースがありません。まずは授業の曜日・時間・月謝を登録しましょう。" action="コースを追加する" :url="route('courses.create')" /></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
