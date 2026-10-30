@extends('layouts.app', ['title' => '月謝'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div data-tour="pay-month" class="flex items-center gap-3">
        <a href="{{ route('payments.index', ['month' => $prev]) }}" class="rounded-lg border border-stone-300 bg-white w-9 h-9 inline-flex items-center justify-center" aria-label="前の月">‹</a>
        <h1 class="text-2xl font-bold">{{ $monthLabel }}の月謝</h1>
        <a href="{{ route('payments.index', ['month' => $next]) }}" class="rounded-lg border border-stone-300 bg-white w-9 h-9 inline-flex items-center justify-center" aria-label="次の月">›</a>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <p class="text-sm text-stone-500">入金 {{ $summary['paid_count'] }}/{{ $summary['count'] }}人 ・ {{ number_format($summary['paid']) }}円 / {{ number_format($summary['total']) }}円</p>
        <details data-tour="pay-export" class="relative">
            <summary class="list-none cursor-pointer rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-sm hover:bg-stone-50">CSVで書き出す ▾</summary>
            <div class="absolute right-0 z-10 mt-1 w-56 rounded-lg border border-stone-200 bg-white shadow-lg py-1 text-sm">
                <a href="{{ route('payments.export', ['month' => $month]) }}" class="block px-4 py-2 hover:bg-stone-50">{{ $monthLabel }}分</a>
                <a href="{{ route('payments.export', ['year' => substr($month, 0, 4)]) }}" class="block px-4 py-2 hover:bg-stone-50">{{ substr($month, 0, 4) }}年の1年分<span class="block text-xs text-stone-500">確定申告や会計ソフト用に</span></a>
            </div>
        </details>
    </div>
</div>

<div data-tour="pay-progress" class="h-2 bg-stone-200 rounded-full mb-5"><div class="h-2 bg-leaf-500 rounded-full" style="width: {{ $summary['rate'] }}%"></div></div>

<div data-tour="pay-list" class="bg-white rounded-xl border border-stone-200 overflow-x-auto">
    <table class="rtable w-full text-sm">
        <thead class="bg-stone-50 text-stone-500 text-left">
            <tr>
                <th class="px-4 py-2.5 font-medium">生徒</th>
                <th class="px-4 py-2.5 font-medium">コース</th>
                <th class="px-4 py-2.5 font-medium text-right">金額</th>
                <th class="px-4 py-2.5 font-medium">状態</th>
                <th class="px-4 py-2.5"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($payments as $p)
            <tr class="border-t border-stone-100 {{ $p->isPaid() ? 'text-stone-500' : '' }}">
                <td class="rt-main px-4 py-3"><a href="{{ route('students.show', $p->student) }}" class="font-medium hover:underline">{{ $p->student->name }}</a></td>
                <td data-label="コース" class="px-4 py-3">{{ $p->student->course?->name ?? '—' }}</td>
                <td data-label="金額" class="px-4 py-3 text-right">{{ number_format($p->amount) }}円</td>
                <td data-label="状態" class="px-4 py-3">
                    @if ($p->isPaid())
                        <span class="text-leaf-600">入金済</span> <span class="text-xs">{{ $p->paid_at->format('n/j') }}・{{ $p->method }}</span>
                    @else
                        <span class="text-clay-600 font-bold">未納</span>
                    @endif
                </td>
                <td class="rt-actions px-4 py-3 text-right whitespace-nowrap">
                    <form method="POST" action="{{ route('payments.toggle', $p) }}" class="inline-flex flex-wrap items-center gap-1">
                        @csrf
                        @if ($p->isPaid())
                            <button class="text-xs text-stone-500 underline">取り消す</button>
                        @else
                            <select name="method" class="text-xs rounded-md border border-stone-300 px-1.5 py-1">
                                <option>現金</option><option>振込</option><option>口座振替</option><option>その他</option>
                            </select>
                            <button class="text-xs rounded-md bg-leaf-500 hover:bg-leaf-600 text-white px-2.5 py-1">入金済みにする</button>
                            <a href="{{ route('messages.create', ['type' => 'unpaid', 'student_id' => $p->student_id]) }}" class="text-xs rounded-md border border-stone-300 px-2 py-1">連絡文</a>
                        @endif
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state message="在籍中の生徒を登録すると、毎月この表に自動で並びます。" action="生徒を登録する" :url="route('students.create')" /></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
