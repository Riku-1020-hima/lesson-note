@extends('layouts.app', ['title' => '振替日を選ぶ'])

@section('content')
<h1 class="text-2xl font-bold mb-1">振替日を選ぶ</h1>
<p class="text-sm text-stone-500 mb-5">{{ $makeup->student->name }}さん・{{ $makeup->absent_date->format('n月j日') }}の欠席分(通常は{{ $makeup->student->course?->schedule_label ?? 'コース未設定' }})</p>

@error('makeup_date')
    <div class="mb-4 rounded-lg bg-clay-50 border border-clay-500/30 text-clay-600 px-4 py-3 text-sm">{{ $message }}</div>
@enderror

<p class="text-sm mb-3">今日から4週間の枠です。空きのある枠をクリックすると予約されます。</p>

@php $byDate = $slots->groupBy(fn ($s) => $s['date']->toDateString()); @endphp

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
    @forelse ($byDate as $date => $daySlots)
        <div class="bg-white rounded-xl border border-stone-200 p-4">
            @php $d = $daySlots->first()['date']; @endphp
            <p class="font-bold text-sm mb-2">{{ $d->format('n月j日') }}({{ \App\Models\Course::WEEKDAYS[$d->dayOfWeek] }})</p>
            @foreach ($daySlots as $slot)
                <form method="POST" action="{{ route('makeups.update', $makeup) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="makeup_course_id" value="{{ $slot['course']->id }}">
                    <input type="hidden" name="makeup_date" value="{{ $date }}">
                    <button @disabled(! $slot['available'])
                        class="w-full flex justify-between items-center text-sm rounded-lg px-3 py-2 mb-1.5 border {{ $slot['available'] ? 'border-leaf-100 bg-leaf-50 hover:bg-leaf-100 text-leaf-700' : 'border-stone-100 bg-stone-50 text-stone-400 cursor-not-allowed' }}">
                        <span>{{ substr($slot['course']->start_time, 0, 5) }} {{ $slot['course']->name }}</span>
                        <span class="text-xs">{{ $slot['available'] ? '空き'.($slot['course']->capacity - $slot['count']) : '満員' }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    @empty
        <p class="text-sm text-stone-500">コースが登録されていません。先にコースを追加してください。</p>
    @endforelse
</div>

<div class="mt-6 flex gap-4 text-sm">
    <a href="{{ route('makeups.index') }}" class="text-stone-500">あとで決める</a>
    <form method="POST" action="{{ route('makeups.update', $makeup) }}" onsubmit="return confirm('この振替を取り消しますか?')">
        @csrf @method('PUT')
        <input type="hidden" name="action" value="cancel">
        <button class="text-clay-600">振替を取り消す</button>
    </form>
</div>
@endsection
