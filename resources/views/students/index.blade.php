@extends('layouts.app', ['title' => '生徒'])

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold">生徒</h1>
    <a data-tour="student-add" href="{{ route('students.create') }}" class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white text-sm font-bold px-4 py-2">+ 生徒を登録</a>
</div>

<form data-tour="student-filter" method="GET" action="{{ route('students.index') }}" class="flex flex-wrap gap-2 mb-2">
    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="名前・ふりがな・保護者名" class="rounded-lg border border-stone-300 px-3 py-2 text-sm flex-1 min-w-[180px]">
    <select name="course_id" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">
        <option value="">すべてのコース</option>
        @foreach ($courses as $c)
            <option value="{{ $c->id }}" @selected((string) $filters['course_id'] === (string) $c->id)>{{ $c->name }}({{ $c->weekday_label }})</option>
        @endforeach
    </select>
    <select name="status" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">
        @foreach (\App\Models\Student::STATUSES + ['all' => 'すべての状態'] as $k => $v)
            <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $v }}</option>
        @endforeach
    </select>
    <button class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm">絞り込む</button>
    @if ($isFiltered)
        <a href="{{ route('students.index', ['reset' => 1]) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm hover:bg-stone-50">リセット</a>
    @endif
</form>
<p class="text-xs text-stone-500 mb-4">
    {{ $students->total() }}人
    @if ($isFiltered) ・絞り込み中(ほかのページに移っても保たれます)@endif
    ・名前をクリックすると登録情報が開きます
</p>

<div class="bg-white rounded-xl border border-stone-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-stone-500 text-left">
            <tr>
                <th class="px-4 py-2.5 font-medium">名前</th>
                <th class="px-4 py-2.5 font-medium">コース</th>
                <th class="px-4 py-2.5 font-medium">保護者</th>
                <th class="px-4 py-2.5 font-medium text-right">月謝</th>
                <th class="px-4 py-2.5 font-medium">状態</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($students as $s)
            <tr class="border-t border-stone-100">
                <td class="px-4 py-3">
                    <button type="button" @if ($loop->first) data-tour="student-name" @endif onclick="toggleDetail({{ $s->id }}, this)" aria-expanded="false" aria-controls="detail-{{ $s->id }}"
                            class="text-left font-medium hover:underline flex items-center gap-1.5">
                        <span class="chev inline-block text-stone-400 text-xs transition-transform">▶</span>{{ $s->name }}
                    </button>
                    @if ($s->kana)<div class="text-xs text-stone-500 ml-4">{{ $s->kana }}</div>@endif
                </td>
                <td class="px-4 py-3">{{ $s->course?->name ?? '—' }}</td>
                <td class="px-4 py-3">{{ $s->guardian_name ?? '—' }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($s->fee) }}円</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('students.status', $s) }}" @if ($loop->first) data-tour="student-status" @endif>
                        @csrf @method('PATCH')
                        <select name="status" onchange="this.form.submit()" aria-label="{{ $s->name }}さんの状態"
                                class="text-xs rounded-full border px-2 py-1 cursor-pointer {{ ['active' => 'bg-leaf-50 text-leaf-700 border-leaf-100', 'paused' => 'bg-amber-50 text-amber-700 border-amber-200'][$s->status] ?? 'bg-stone-100 text-stone-500 border-stone-200' }}">
                            @foreach (\App\Models\Student::STATUSES as $k => $v)
                                <option value="{{ $k }}" @selected($s->status === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        <noscript><button class="text-xs underline ml-1">変更</button></noscript>
                    </form>
                </td>
            </tr>
            <tr id="detail-{{ $s->id }}" class="hidden bg-stone-50/70">
                <td colspan="5" class="px-4 pb-4 pt-1">
                    <dl class="grid sm:grid-cols-3 gap-x-6 gap-y-2 text-sm bg-white border border-stone-200 rounded-lg p-4">
                        <div><dt class="text-xs text-stone-500">入会日</dt><dd>{{ $s->enrolled_on?->format('Y年n月j日') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">コース</dt><dd>{{ $s->course ? $s->course->name.'('.$s->course->schedule_label.')' : '未設定' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">月謝</dt><dd>{{ number_format($s->fee) }}円{{ $s->monthly_fee === null ? '(コースの月謝)' : '(個別設定)' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">保護者</dt><dd>{{ $s->guardian_name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">メール</dt><dd class="break-all">{{ $s->guardian_email ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">電話</dt><dd>{{ $s->guardian_phone ?? '—' }}</dd></div>
                        <div class="sm:col-span-3"><dt class="text-xs text-stone-500">メモ</dt><dd class="whitespace-pre-line">{{ $s->note ?: '—' }}</dd></div>
                        <div class="sm:col-span-3 flex gap-4 pt-1 border-t border-stone-100">
                            <a href="{{ route('students.show', $s) }}" class="text-leaf-600 pt-2">月謝・振替の履歴を見る →</a>
                            <a href="{{ route('students.edit', $s) }}" class="text-leaf-600 pt-2">編集する</a>
                        </div>
                    </dl>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">
                @if ($isFiltered)
                    <x-empty-state message="条件に合う生徒がいません。">
                        <a href="{{ route('students.index', ['reset' => 1]) }}" class="inline-block rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm hover:bg-stone-50">絞り込みをリセット</a>
                    </x-empty-state>
                @elseif ($courses->isEmpty())
                    <x-empty-state message="生徒を登録する前に、通ってもらうコースを登録しましょう。" action="先にコースを追加する" :url="route('courses.create')" />
                @else
                    <x-empty-state message="まだ生徒がいません。1人目を登録してみましょう。" action="生徒を登録する" :url="route('students.create')" />
                @endif
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $students->links() }}</div>

<script>
    function toggleDetail(id, btn) {
        const row = document.getElementById('detail-' + id);
        const open = row.classList.toggle('hidden') === false;
        btn.setAttribute('aria-expanded', open);
        btn.querySelector('.chev').style.transform = open ? 'rotate(90deg)' : '';
    }
</script>
@endsection
