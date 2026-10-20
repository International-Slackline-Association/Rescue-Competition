<x-layout :title="$page['title']">
    <article class="prose prose-lg mx-auto prose-headings:tracking-tight prose-headings:text-label prose-headings:font-semibold prose-a:text-label">
        {!! $page['html'] !!}
    </article>

    @if ($images)
        <section class="mx-auto mt-10 grid max-w-5xl gap-10">
            @foreach ($images as $image)
                <figure>
                    <a href="{{ $image['url'] }}">
                        <img src="{{ $image['url'] }}" alt="{{ $image['caption'] }}" loading="lazy" class="w-full rounded-lg border border-line bg-white">
                    </a>
                    <figcaption class="mt-3 flex flex-wrap items-center justify-between gap-3 text-sm text-muted">
                        {{ $image['caption'] }}
                        @if ($image['pdf'])
                            <a href="{{ $image['pdf']['url'] }}" download="{{ $image['pdf']['name'] }}" class="btn-ghost">
                                Download PDF <span class="text-muted">({{ $image['pdf']['size'] }})</span>
                            </a>
                        @endif
                    </figcaption>
                </figure>
            @endforeach
        </section>
    @endif
</x-layout>
