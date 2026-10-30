@extends('layouts.app', ['title' => '欠席を登録'])

@section('content')
<h1 class="text-2xl font-bold mb-5">欠席を登録</h1>

<form method="POST" action="{{ route('makeups.store') }}" data-draft="makeup-new" class="bg-white rounded-xl border border-stone-200 p-6 max-w-xl space-y-4">
    @csrf
    <x-field label="生徒" name="student_id">
        <select name="student_id" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
            <option value="">選んでください</option>
            @foreach ($students as $s)
                <option value="{{ $s->id }}" @selected(old('student_id', $selected) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field label="欠席日" name="absent_date">
        <input type="date" name="absent_date" value="{{ old('absent_date', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
    </x-field>
    <x-field label="理由(任意)" name="reason">
        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="例:体調不良、学校行事" class="w-full rounded-lg border border-stone-300 px-3 py-2">
    </x-field>
    <button class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white font-bold px-5 py-2">登録して振替日を選ぶ</button>
</form>
@endsection
