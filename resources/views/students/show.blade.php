@extends('layouts.app', ['title' => $student->name])

@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <p class="text-sm text-stone-500">{{ $student->kana }}</p>
        <h1 class="text-2xl font-bold">{{ $student->name }}
            <span class="align-middle text-xs rounded-full px-2 py-0.5 {{ $student->status === 'active' ? 'bg-leaf-50 text-leaf-700' : 'bg-stone-100 text-stone-500' }}">{{ $student->status_label }}</span>
        </h1>
    </div>
    <div class="flex gap-2 text-sm">
        <a href="{{ route('makeups.create', ['student_id' => $student->id]) }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2">欠席を登録</a>
        <a href="{{ route('messages.create', ['student_id' => $student->id]) }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2">連絡文を作る</a>
        <a href="{{ route('students.edit', $student) }}" class="rounded-lg bg-leaf-500 text-white font-bold px-3 py-2">編集</a>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <section class="bg-white rounded-xl border border-stone-200 p-5 text-sm space-y-2">
        <h2 class="font-bold mb-2">基本情報</h2>
        <p><span class="text-stone-500 inline-block w-20">コース</span>{{ $student->course ? $student->course->name.'('.$student->course->schedule_label.')' : '未設定' }}</p>
        <p><span class="text-stone-500 inline-block w-20">月謝</span>{{ number_format($student->fee) }}円</p>
        <p><span class="text-stone-500 inline-block w-20">入会日</span>{{ $student->enrolled_on?->format('Y年n月j日') ?? '—' }}</p>
        <p><span class="text-stone-500 inline-block w-20">保護者</span>{{ $student->guardian_name ?? '—' }}</p>
        <p><span class="text-stone-500 inline-block w-20">メール</span>{{ $student->guardian_email ?? '—' }}</p>
        <p><span class="text-stone-500 inline-block w-20">電話</span>{{ $student->guardian_phone ?? '—' }}</p>
        @if ($student->note)
            <p class="pt-2 border-t border-stone-100 whitespace-pre-line">{{ $student->note }}</p>
        @endif
    </section>

    <section class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="font-bold mb-3 text-sm">月謝の履歴</h2>
        @forelse ($student->payments as $p)
            <div class="flex justify-between text-sm py-1.5 border-t border-stone-100 first:border-0">
                <span>{{ str_replace('-', '年', $p->year_month) }}月</span>
                <span>{{ number_format($p->amount) }}円
                    @if ($p->isPaid())
                        <span class="text-leaf-600 ml-1">入金済</span>
                    @else
                        <span class="text-clay-600 ml-1">未納</span>
                    @endif
                </span>
            </div>
        @empty
            <p class="text-sm text-stone-500">まだ記録がありません。</p>
        @endforelse
    </section>

    <section class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="font-bold mb-3 text-sm">振替の履歴</h2>
        @forelse ($student->makeupLessons->sortByDesc('absent_date') as $m)
            <div class="text-sm py-1.5 border-t border-stone-100 first:border-0">
                <div class="flex justify-between">
                    <span>{{ $m->absent_date->format('n/j') }} 欠席</span>
                    <span class="text-stone-500">{{ $m->status_label }}</span>
                </div>
                @if ($m->makeup_date)
                    <p class="text-xs text-stone-500">→ {{ $m->makeup_date->format('n/j') }} {{ $m->makeupCourse?->name }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-stone-500">まだ記録がありません。</p>
        @endforelse
    </section>
</div>
@endsection
