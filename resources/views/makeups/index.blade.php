@extends('layouts.app', ['title' => '振替'])

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold">振替</h1>
    <a data-tour="makeup-add" href="{{ route('makeups.create') }}" class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white text-sm font-bold px-4 py-2">+ 欠席を登録</a>
</div>

<div data-tour="makeup-tabs" class="flex gap-2 mb-4 text-sm">
    @foreach (['open' => '対応中', 'done' => '完了', 'cancelled' => '取消', 'all' => 'すべて'] as $k => $v)
        <a href="{{ route('makeups.index', ['status' => $k]) }}" class="rounded-full px-3 py-1 border {{ $status === $k ? 'bg-ink text-white border-ink' : 'bg-white border-stone-300' }}">{{ $v }}</a>
    @endforeach
</div>

<div data-tour="makeup-list" class="bg-white rounded-xl border border-stone-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-stone-50 text-stone-500 text-left">
            <tr>
                <th class="px-4 py-2.5 font-medium">生徒</th>
                <th class="px-4 py-2.5 font-medium">欠席日</th>
                <th class="px-4 py-2.5 font-medium">振替先</th>
                <th class="px-4 py-2.5 font-medium">状態</th>
                <th class="px-4 py-2.5"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($makeups as $m)
            <tr class="border-t border-stone-100">
                <td class="px-4 py-3"><a href="{{ route('students.show', $m->student) }}" class="font-medium hover:underline">{{ $m->student->name }}</a>
                    <div class="text-xs text-stone-500">{{ $m->student->course?->name }}</div></td>
                <td class="px-4 py-3">{{ $m->absent_date->format('n/j') }}<div class="text-xs text-stone-500">{{ $m->reason }}</div></td>
                <td class="px-4 py-3">
                    @if ($m->makeup_date)
                        {{ $m->makeup_date->format('n/j') }}({{ \App\Models\Course::WEEKDAYS[$m->makeup_date->dayOfWeek] }}) {{ $m->makeupCourse?->name }}
                    @else
                        <span class="text-stone-400">未定</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs rounded-full px-2 py-0.5 {{ ['pending' => 'bg-clay-50 text-clay-600', 'booked' => 'bg-leaf-50 text-leaf-700'][$m->status] ?? 'bg-stone-100 text-stone-500' }}">{{ $m->status_label }}</span>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if (in_array($m->status, ['pending', 'booked']))
                        <a href="{{ route('makeups.edit', $m) }}" class="text-leaf-600 mr-2">{{ $m->status === 'pending' ? '振替日を選ぶ' : '変更' }}</a>
                        @if ($m->status === 'booked')
                            <form method="POST" action="{{ route('makeups.update', $m) }}" class="inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="action" value="done">
                                <button class="text-xs rounded-md border border-stone-300 px-2 py-1">受講済みにする</button>
                            </form>
                            <a href="{{ route('messages.create', ['type' => 'makeup', 'student_id' => $m->student_id]) }}" class="text-xs rounded-md border border-stone-300 px-2 py-1 ml-1">案内文</a>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state message="{{ $status === 'open' ? '対応中の振替はありません。お休みの連絡が来たら「+ 欠席を登録」から記録しましょう。' : '該当する振替はありません。' }}" /></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
