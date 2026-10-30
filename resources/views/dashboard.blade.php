@extends('layouts.app', ['title' => 'ホーム'])

@section('content')
<div class="flex items-end justify-between mb-6">
    <div>
        <p class="text-sm text-stone-500">{{ now()->format('Y年n月j日') }}({{ \App\Models\Course::WEEKDAYS[now()->dayOfWeek] }})</p>
        <h1 class="text-2xl font-bold">こんにちは、{{ auth()->user()->name }}さん</h1>
    </div>
    <a href="{{ route('students.create') }}" class="hidden sm:inline-block rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white text-sm font-bold px-4 py-2">+ 生徒を登録</a>
</div>

@unless ($setupRequiredDone)
    @php $doneCount = collect($setup)->where('done', true)->count(); @endphp
    <section data-tour="setup" class="bg-white rounded-xl border-2 border-leaf-100 p-5 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h2 class="font-bold">はじめにやること</h2>
            <span class="text-sm text-stone-500">{{ $doneCount }} / {{ count($setup) }} 完了</span>
        </div>
        <div class="h-1.5 bg-stone-100 rounded-full mb-4"><div class="h-1.5 bg-leaf-500 rounded-full transition-all" style="width: {{ round($doneCount / count($setup) * 100) }}%"></div></div>
        @php $nextFound = false; @endphp
        <ol class="space-y-2">
            @foreach ($setup as $n => $step)
                @php $isNext = ! $step['done'] && ! $nextFound; if ($isNext) $nextFound = true; @endphp
                <li>
                    <a href="{{ $step['url'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 border {{ $isNext ? 'border-leaf-500 bg-leaf-50' : 'border-transparent hover:bg-stone-50' }}">
                        @if ($step['done'])
                            <span class="w-7 h-7 shrink-0 rounded-full bg-leaf-500 text-white inline-flex items-center justify-center text-sm" aria-label="完了">✓</span>
                        @else
                            <span class="w-7 h-7 shrink-0 rounded-full border-2 {{ $isNext ? 'border-leaf-500 text-leaf-700' : 'border-stone-300 text-stone-400' }} inline-flex items-center justify-center text-sm font-bold">{{ $n + 1 }}</span>
                        @endif
                        <span class="flex-1">
                            <span class="block text-sm font-medium {{ $step['done'] ? 'line-through text-stone-400' : '' }}">{{ $step['title'] }}</span>
                            <span class="block text-xs text-stone-500">{{ $step['text'] }}</span>
                        </span>
                        @if ($isNext)<span class="text-sm font-bold text-leaf-700 whitespace-nowrap">はじめる →</span>@endif
                    </a>
                </li>
            @endforeach
        </ol>
    </section>
@endunless

<div data-tour="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div data-tour="stat-active" class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">在籍中の生徒</p>
        <p class="text-2xl font-bold mt-1">{{ $activeCount }}<span class="text-sm font-normal text-stone-500 ml-1">人</span></p>
    </div>
    <div data-tour="stat-rate" class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">{{ now()->format('n月') }}の入金率</p>
        <p class="text-2xl font-bold mt-1">{{ $summary['rate'] }}<span class="text-sm font-normal text-stone-500 ml-1">%</span></p>
        <div class="h-1.5 bg-stone-100 rounded-full mt-2"><div class="h-1.5 bg-leaf-500 rounded-full" style="width: {{ $summary['rate'] }}%"></div></div>
    </div>
    <div data-tour="stat-unpaid" class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">未納の合計</p>
        <p class="text-2xl font-bold mt-1 {{ $summary['unpaid'] > 0 ? 'text-clay-600' : '' }}">{{ number_format($summary['unpaid']) }}<span class="text-sm font-normal text-stone-500 ml-1">円</span></p>
    </div>
    <div data-tour="stat-makeups" class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">振替待ち</p>
        <p class="text-2xl font-bold mt-1">{{ $pendingMakeups->count() }}<span class="text-sm font-normal text-stone-500 ml-1">件</span></p>
        @if ($urgentMakeups->isNotEmpty())
            <p class="text-xs font-bold text-clay-600 mt-1">うち期限が近い・切れた {{ $urgentMakeups->count() }}件</p>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <section data-tour="unpaid-list" class="bg-white rounded-xl border border-stone-200 p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold">{{ now()->format('n月') }}の未納</h2>
            <a href="{{ route('payments.index') }}" class="text-sm text-leaf-600">月謝表を開く →</a>
        </div>
        @forelse ($unpaid as $p)
            <div class="flex items-center justify-between py-2.5 border-t border-stone-100 first:border-0">
                <div>
                    <a href="{{ route('students.show', $p->student) }}" class="font-medium hover:underline">{{ $p->student->name }}</a>
                    <span class="text-xs text-stone-500 ml-2">{{ $p->student->course?->name }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ number_format($p->amount) }}円</span>
                    <form method="POST" action="{{ route('payments.toggle', $p) }}">
                        @csrf
                        <button class="text-xs rounded-md border border-leaf-500 text-leaf-600 px-2 py-1 hover:bg-leaf-50">入金済みにする</button>
                    </form>
                    <a href="{{ route('messages.create', ['type' => 'unpaid', 'student_id' => $p->student_id]) }}" class="text-xs rounded-md border border-stone-300 px-2 py-1 hover:bg-stone-50">連絡文</a>
                </div>
            </div>
        @empty
            @if ($activeCount === 0)
                <x-empty-state message="生徒を登録すると、今月まだ月謝を払っていない生徒がここに出ます。" />
            @else
                <p class="text-sm text-stone-500 py-4">今月の未納はありません。</p>
            @endif
        @endforelse
    </section>

    <div class="space-y-4">
        <section data-tour="today" class="bg-white rounded-xl border border-stone-200 p-5">
            <h2 class="font-bold mb-3">今日のクラス</h2>
            @forelse ($todayCourses as $c)
                <div class="flex justify-between text-sm py-1.5">
                    <span>{{ substr($c->start_time, 0, 5) }} {{ $c->name }}</span>
                    <span class="text-stone-500">{{ $c->attendeeCountOn(now()) }}/{{ $c->capacity }}人</span>
                </div>
            @empty
                <p class="text-sm text-stone-500">{{ auth()->user()->courses()->exists() ? '今日のクラスはありません。' : 'コースを登録すると、今日の授業がここに出ます。' }}</p>
            @endforelse
        </section>

        <section data-tour="makeup-list" class="bg-white rounded-xl border border-stone-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold">振替待ち</h2>
                <a href="{{ route('makeups.create') }}" class="text-sm text-leaf-600">+ 欠席を登録</a>
            </div>
            @forelse ($pendingMakeups as $m)
                <a href="{{ route('makeups.edit', $m) }}" class="flex justify-between items-center gap-2 text-sm py-1.5 hover:underline">
                    <span>{{ $m->student->name }}</span>
                    <span class="text-right">
                        <span class="text-stone-500">{{ $m->absent_date->format('n/j') }} 欠席</span>
                        <x-deadline-badge :info="$m->deadlineInfo($deadlineDays)" class="ml-1" />
                    </span>
                </a>
            @empty
                <p class="text-sm text-stone-500">振替待ちはありません。</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
