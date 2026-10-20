@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · " : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    <header class="border-b border-line bg-white">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-4">
            <a href="{{ route('home') }}" class="mr-auto flex items-center gap-3" aria-label="{{ config('app.name') }} home">
                <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="h-9 w-auto">
                <span class="leading-none">
                    <span class="block text-lg font-bold text-label">Slack Rescue</span>
                    <span class="block text-[0.65rem] font-semibold tracking-[0.25em] text-main-dark uppercase">Competition</span>
                </span>
            </a>
            <a href="{{ route('ranking') }}" class="text-sm text-label hover:text-main-dark">Ranking</a>
            <a href="{{ route('rescues.index') }}" class="text-sm text-label hover:text-main-dark">Rescues</a>
            <a href="{{ route('page', 'rules') }}" class="text-sm text-label hover:text-main-dark">Rules</a>
            <a href="{{ route('page', 'rescue_graphics') }}" class="text-sm text-label hover:text-main-dark">Graphics</a>
            @auth
                <a href="{{ route('people.show', auth()->user()) }}" class="text-sm text-label hover:text-main-dark">My profile</a>
                @if (auth()->user()->is_admin)
                    <a href="{{ url('/admin') }}" class="text-sm text-label hover:text-main-dark">Admin</a>
                @endif
                <a href="{{ route('rescues.create') }}" class="btn">Submit a rescue</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm text-muted hover:text-ink">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn">Log in with ISA account</a>
            @endauth
        </nav>
    </header>

    @if (session('status'))
        <div class="mx-auto mt-6 max-w-6xl px-4">
            <p class="rounded-lg bg-label/10 px-4 py-3 text-sm text-label">{{ session('status') }}</p>
        </div>
    @endif

    <main class="mx-auto max-w-6xl px-4 py-10">
        {{ $slot }}
    </main>

    <footer class="border-t border-line py-8 text-center text-sm text-muted">
        A practice competition supported by the
        <a href="https://www.slacklineinternational.org" class="underline hover:text-ink">International Slackline Association</a>
    </footer>
</body>
</html>
