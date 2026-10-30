@extends('layouts.app', ['title' => 'ログイン'])

@section('content')
<div class="max-w-sm mx-auto mt-12">
    <div class="text-center mb-8">
        <div class="inline-flex w-12 h-12 rounded-xl bg-leaf-500 text-white items-center justify-center text-xl font-bold">L</div>
        <h1 class="text-2xl font-bold mt-3">レッスンノート</h1>
        <p class="text-sm text-stone-500 mt-1">月謝と振替を、ノート1冊ぶんの手軽さで。</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
        @csrf
        <x-field label="メールアドレス" name="email">
            <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border-stone-300 border px-3 py-2">
        </x-field>
        <x-field label="パスワード" name="password">
            <input type="password" name="password" required class="w-full rounded-lg border-stone-300 border px-3 py-2">
        </x-field>
        <div class="flex items-center justify-between gap-2 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember"> ログインしたままにする</label>
            <a href="{{ route('password.request') }}" class="text-leaf-600 underline">パスワードを忘れた方</a>
        </div>
        <button class="w-full rounded-lg bg-leaf-500 hover:bg-leaf-600 text-white font-bold py-2.5">ログイン</button>
    </form>
    <p class="text-center text-sm mt-4">はじめての方は <a href="{{ route('register') }}" class="text-leaf-600 underline">無料で登録</a></p>
</div>
@endsection
