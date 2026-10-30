@props(['info'])

{{-- 振替の期限の表示(期限なし・振替待ちでないときは何も出さない) --}}
@if ($info)
    @if ($info['level'] === 'expired')
        <span {{ $attributes->merge(['class' => 'inline-block text-xs rounded-full px-2 py-0.5 bg-clay-500 text-white font-bold']) }}>期限切れ({{ $info['date']->format('n/j') }})</span>
    @elseif ($info['level'] === 'soon')
        <span {{ $attributes->merge(['class' => 'inline-block text-xs rounded-full px-2 py-0.5 bg-amber-100 text-amber-800 font-bold']) }}>期限 {{ $info['date']->format('n/j') }}・{{ $info['left'] === 0 ? '今日まで' : 'あと'.$info['left'].'日' }}</span>
    @else
        <span {{ $attributes->merge(['class' => 'inline-block text-xs text-stone-500']) }}>期限 {{ $info['date']->format('n/j') }}</span>
    @endif
@endif
