@extends('layouts.app', ['title' => 'AI連絡文'])

@section('content')
<h1 class="text-2xl font-bold mb-1">保護者への連絡文</h1>
<p class="text-sm text-stone-500 mb-5">
    金額や日付は登録データから自動で入ります。
    @if ($aiEnabled)
        AIが読みやすい文面に整えます。
    @else
        いまはテンプレートで作成中(.env に ANTHROPIC_API_KEY を設定するとAIが文面を整えます)。
    @endif
</p>

<div class="grid lg:grid-cols-2 gap-4">
    <form data-tour="msg-form" method="POST" action="{{ route('messages.generate') }}" data-draft="message" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
        @csrf
        <x-field label="用件" name="type">
            <select name="type" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                @foreach ($types as $k => $v)
                    <option value="{{ $k }}" @selected($input['type'] === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="生徒" name="student_id">
            <select name="student_id" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
                <option value="">選んでください</option>
                @foreach ($students as $s)
                    <option value="{{ $s->id }}" @selected($input['student_id'] == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="追加の指示・お知らせ内容(任意)" name="extra" hint="例:やわらかめに / 12月の発表会のお知らせを入れて">
            <textarea name="extra" rows="3" class="w-full rounded-lg border border-stone-300 px-3 py-2">{{ old('extra', $input['extra']) }}</textarea>
        </x-field>
        <button class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white font-bold px-5 py-2">文面を作る</button>
    </form>

    <section data-tour="msg-result" class="bg-white rounded-xl border border-stone-200 p-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold">できあがった文面</h2>
            @if ($result)
                <span class="text-xs rounded-full px-2 py-0.5 {{ $result['source'] === 'ai' ? 'bg-leaf-50 text-leaf-700' : 'bg-stone-100 text-stone-500' }}">{{ $result['source'] === 'ai' ? 'AIで作成' : 'テンプレート' }}</span>
            @endif
        </div>
        @if ($result)
            <textarea id="result" rows="12" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm leading-relaxed">{{ $result['text'] }}</textarea>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('result').value); this.textContent='コピーしました'" class="mt-2 rounded-lg border border-stone-300 px-4 py-2 text-sm">コピーする</button>
            <p class="text-xs text-stone-500 mt-2">送る前に内容を確認してください。LINEやメールに貼り付けて使えます。</p>
        @else
            <p class="text-sm text-stone-500">左で用件と生徒を選ぶと、ここに文面が出ます。</p>
        @endif
    </section>
</div>
@endsection
