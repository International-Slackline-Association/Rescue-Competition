@props(['user', 'size' => 'size-8'])
@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="" {{ $attributes->class([$size, 'rounded-full object-cover']) }}>
@else
    <span {{ $attributes->class([$size, 'grid place-items-center rounded-full bg-ink/10 text-xs font-semibold text-ink']) }}>
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    </span>
@endif
