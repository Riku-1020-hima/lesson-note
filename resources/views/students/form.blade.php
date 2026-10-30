@extends('layouts.app', ['title' => $student->exists ? '生徒を編集' : '生徒を登録'])

@section('content')
<h1 class="text-2xl font-bold mb-5">{{ $student->exists ? '生徒を編集' : '生徒を登録' }}</h1>

<form method="POST" action="{{ $student->exists ? route('students.update', $student) : route('students.store') }}" data-draft="student-{{ $student->id ?? 'new' }}" class="bg-white rounded-xl border border-stone-200 p-6 max-w-2xl space-y-4">
    @csrf
    @if ($student->exists) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-3">
        <x-field label="名前" name="name">
            <input type="text" name="name" value="{{ old('name', $student->name) }}" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
        <x-field label="ふりがな" name="kana">
            <input type="text" name="kana" value="{{ old('kana', $student->kana) }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
        <div data-tour="student-course"><x-field label="コース" name="course_id">
            <select name="course_id" id="course-select" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                <option value="" data-fee="">未設定</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}" data-fee="{{ $c->monthly_fee }}" @selected(old('course_id', $student->course_id) == $c->id)>{{ $c->name }}({{ $c->schedule_label }}・{{ number_format($c->monthly_fee) }}円)</option>
                @endforeach
            </select>
        </x-field></div>
        <div data-tour="student-fee"><x-field label="月謝(円)" name="monthly_fee" hint="コースを選ぶと自動で入ります。兄弟割引などがあれば書き換えてください">
            {{-- 個別設定がなければコースの月謝を最初から表示しておく --}}
            <input type="number" name="monthly_fee" id="fee-input" value="{{ old('monthly_fee', $student->monthly_fee ?? $student->course?->monthly_fee) }}" min="0" step="100" class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field></div>
        <script>
            (() => {
                const select = document.getElementById('course-select');
                const fee = document.getElementById('fee-input');
                const courseFee = () => select.selectedOptions[0]?.dataset.fee ?? '';
                let lastAuto = courseFee();
                select.addEventListener('focus', () => { lastAuto = courseFee(); });
                select.addEventListener('change', () => {
                    // 空欄か、前のコースの金額のままなら新しいコースの金額に入れ替える(手で変えた金額は残す)
                    if (fee.value === '' || fee.value === String(lastAuto)) {
                        fee.value = courseFee();
                        fee.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    lastAuto = courseFee();
                });
            })();
        </script>
    </div>

    <div data-tour="student-guardian" class="grid sm:grid-cols-3 gap-3">
        <x-field label="保護者名" name="guardian_name">
            <input type="text" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
        <x-field label="保護者メール" name="guardian_email">
            <input type="email" name="guardian_email" value="{{ old('guardian_email', $student->guardian_email) }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
        <x-field label="保護者電話" name="guardian_phone">
            <input type="tel" name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone) }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
        <x-field label="入会日" name="enrolled_on">
            <input type="date" name="enrolled_on" value="{{ old('enrolled_on', $student->enrolled_on?->format('Y-m-d')) }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
        <x-field label="状態" name="status">
            <select name="status" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                @foreach (\App\Models\Student::STATUSES as $k => $v)
                    <option value="{{ $k }}" @selected(old('status', $student->status) === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </x-field>
    </div>

    <x-field label="メモ" name="note">
        <textarea name="note" rows="3" class="w-full rounded-lg border border-stone-300 px-3 py-2">{{ old('note', $student->note) }}</textarea>
    </x-field>

    <div class="flex items-center justify-between pt-2">
        <button class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white font-bold px-5 py-2">保存する</button>
        <a href="{{ $student->exists ? route('students.show', $student) : route('students.index') }}" class="text-sm text-stone-500">キャンセル</a>
    </div>
</form>

@if ($student->exists)
    <form method="POST" action="{{ route('students.destroy', $student) }}" class="max-w-2xl mt-4 text-right" onsubmit="return confirm('この生徒を削除しますか?月謝と振替の記録も消えます。退会の場合は「状態」を退会にするのがおすすめです。')">
        @csrf @method('DELETE')
        <button class="text-sm text-clay-600">この生徒を削除</button>
    </form>
@endif
@endsection
