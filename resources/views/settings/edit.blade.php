@extends('layouts.app', ['title' => '設定'])

@section('content')
<h1 class="text-2xl font-bold mb-5">設定</h1>

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
    @csrf @method('PUT')

    <section data-tour="set-names" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
        <h2 class="font-bold">教室とアプリの名前</h2>
        <x-field label="教室名" name="name" hint="画面右上と、保護者への連絡文の署名に使われます">
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>
        <x-field label="アプリの表示名" name="app_name" hint="画面左上とブラウザのタブに出る名前です。空欄なら「{{ \App\Models\User::DEFAULT_APP_NAME }}」になります(30文字まで)">
            <input type="text" name="app_name" value="{{ old('app_name', $user->app_name) }}" maxlength="30" placeholder="{{ \App\Models\User::DEFAULT_APP_NAME }}"
                   oninput="document.getElementById('logo-letter').textContent = (this.value.trim() || '{{ \App\Models\User::DEFAULT_APP_NAME }}').slice(0, 1)"
                   class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>

        <div data-tour="set-icon">
            <span class="block text-sm font-medium mb-2">アイコン</span>
            <div class="flex items-center gap-4">
                {{-- プレビュー:画像があれば画像、なければ名前の頭文字 --}}
                <div class="relative w-16 h-16 shrink-0">
                    <img id="logo-img" src="{{ $user->logo_url }}" alt="アイコンのプレビュー"
                         class="w-16 h-16 rounded-xl object-cover bg-stone-100 {{ $user->logo_url ? '' : 'hidden' }}">
                    <span id="logo-letter"
                          class="w-16 h-16 rounded-xl bg-leaf-500 text-white items-center justify-center text-2xl font-bold {{ $user->logo_url ? 'hidden' : 'inline-flex' }}">{{ mb_substr($user->display_app_name, 0, 1) }}</span>
                </div>
                <div class="space-y-2 text-sm">
                    <label class="inline-block rounded-lg border border-stone-300 bg-white px-4 py-2 cursor-pointer hover:bg-stone-50">
                        画像を選ぶ
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" onchange="previewLogo(this)">
                    </label>
                    <p id="logo-file-name" class="text-xs text-stone-500">JPG・PNG・WebP・GIF、2MBまで。正方形の画像がきれいに表示されます</p>
                    @if ($user->logo_url)
                        <label class="flex items-center gap-2 text-stone-600">
                            <input type="checkbox" name="remove_logo" value="1" onchange="toggleRemove(this)"> 画像を外して頭文字に戻す
                        </label>
                    @endif
                </div>
            </div>
            @error('logo')
                <span class="block text-xs text-clay-600 mt-2">{{ $message }}</span>
            @enderror
        </div>
    </section>

    <script>
        const logoImg = document.getElementById('logo-img');
        const logoLetter = document.getElementById('logo-letter');
        const savedLogo = @json($user->logo_url);

        function showImage(src) {
            logoImg.src = src;
            logoImg.classList.remove('hidden');
            logoLetter.classList.add('hidden');
            logoLetter.classList.remove('inline-flex');
        }
        function showLetter() {
            logoImg.classList.add('hidden');
            logoLetter.classList.remove('hidden');
            logoLetter.classList.add('inline-flex');
        }
        function previewLogo(input) {
            const file = input.files[0];
            if (! file) return;
            showImage(URL.createObjectURL(file));
            document.getElementById('logo-file-name').textContent = '選んだ画像:' + file.name + '(保存すると反映されます)';
            const remove = document.querySelector('[name=remove_logo]');
            if (remove) remove.checked = false;
        }
        function toggleRemove(box) {
            if (box.checked) {
                showLetter();
                document.querySelector('[name=logo]').value = '';
            } else if (savedLogo) {
                showImage(savedLogo);
            }
        }
    </script>

    <section data-tour="set-makeup" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
        <h2 class="font-bold">振替のルール</h2>
        <x-field label="振替の期限" name="makeup_deadline_days" hint="欠席した日から何日以内に振替するかを決めます。空欄なら期限なしです。期限が7日以内に迫ると、ホームと振替の一覧でお知らせします">
            <div class="flex items-center gap-2">
                <span class="text-sm whitespace-nowrap">欠席日から</span>
                <input type="number" name="makeup_deadline_days" value="{{ old('makeup_deadline_days', $user->makeup_deadline_days) }}" min="1" max="365" placeholder="例:60"
                       class="w-28 rounded-lg border border-stone-300 px-3 py-2">
                <span class="text-sm whitespace-nowrap">日以内</span>
            </div>
        </x-field>
    </section>

    <section data-tour="set-login" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
        <h2 class="font-bold">ログイン情報</h2>
        <x-field label="メールアドレス" name="email">
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border border-stone-300 px-3 py-2">
        </x-field>

        <div class="pt-2 border-t border-stone-100">
            <p class="text-sm text-stone-500 mb-3">パスワードを変えるときだけ入力してください。</p>
            <div class="grid sm:grid-cols-3 gap-3">
                <x-field label="現在のパスワード" name="current_password">
                    <input type="password" name="current_password" autocomplete="current-password" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                </x-field>
                <x-field label="新しいパスワード" name="password">
                    <input type="password" name="password" autocomplete="new-password" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                </x-field>
                <x-field label="新しいパスワード(確認)" name="password_confirmation">
                    <input type="password" name="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                </x-field>
            </div>
        </div>
    </section>

    <button class="rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white font-bold px-6 py-2.5">まとめて保存する</button>
</form>
@endsection
