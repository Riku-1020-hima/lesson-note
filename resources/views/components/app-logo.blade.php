@props(['user' => null, 'size' => 'w-7 h-7 text-sm'])

@php
    $user ??= auth()->user();
    $name = $user?->display_app_name ?? \App\Models\User::DEFAULT_APP_NAME;
@endphp

@if ($user?->logo_url)
    <img src="{{ $user->logo_url }}" alt="" {{ $attributes->merge(['class' => "$size shrink-0 rounded-lg object-cover bg-stone-100"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$size shrink-0 inline-flex rounded-lg bg-leaf-500 text-white items-center justify-center font-bold"]) }}>{{ mb_substr($name, 0, 1) }}</span>
@endif
