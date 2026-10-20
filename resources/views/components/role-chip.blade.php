@props(['role'])
<span {{ $attributes->class(['inline-block h-3 w-5 shrink-0 rounded-sm', $role->chipClass()]) }} aria-hidden="true"></span>
