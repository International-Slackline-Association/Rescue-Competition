<?php

namespace App\Http\Controllers;

use App\Enums\RescueRole;
use App\Models\Rescue;
use App\Models\User;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Info pages are Markdown files in resources/content, edited through git.
 */
class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'page' => $this->render('home'),
            'leaders' => collect(RescueRole::cases())
                ->mapWithKeys(fn (RescueRole $role) => [$role->value => User::ranking($role)->limit(3)->get()]),
            'latest' => Rescue::with('participants')->latest('happened_on')->limit(6)->get(),
        ]);
    }

    public function show(string $slug): View
    {
        return view('pages.show', ['page' => $this->render($slug), 'images' => $this->images($slug)]);
    }

    /**
     * Images dropped into public/images/{slug}/ are shown under the page, sorted by file name.
     * "02-ground-rescue.jpg" and "1.1 GROUND RESCUE.png" both get the caption "Ground rescue".
     * A PDF with the same name next to an image is offered as a download.
     *
     * @return list<array{url: string, caption: string, pdf: ?array{url: string, name: string, size: string}}>
     */
    private function images(string $slug): array
    {
        $files = array_filter(
            glob(public_path("images/{$slug}/*")) ?: [],
            fn (string $file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']),
        );
        natcasesort($files);

        return array_values(array_map(function (string $file) use ($slug) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $caption = Str::of($name)->replaceMatches('/^[\d.]+[-_ ]*/', '')->replace(['-', '_'], ' ')->trim();
            $pdf = dirname($file)."/{$name}.pdf";

            return [
                'url' => asset("images/{$slug}/".rawurlencode(basename($file))),
                'caption' => ($caption->upper()->toString() === $caption->toString() ? $caption->lower() : $caption)->ucfirst()->toString(),
                'pdf' => is_file($pdf) ? [
                    'url' => asset("images/{$slug}/".rawurlencode(basename($pdf))),
                    'name' => basename($pdf),
                    'size' => Number::fileSize(filesize($pdf), precision: 1),
                ] : null,
            ];
        }, $files));
    }

    /**
     * @return array{title: string, html: string}
     */
    private function render(string $slug): array
    {
        $path = resource_path("content/{$slug}.md");
        abort_unless(preg_match('/^[a-z0-9_-]+$/', $slug) && is_file($path), 404);

        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FrontMatterExtension);

        $result = (new MarkdownConverter($environment))->convert(file_get_contents($path));
        $meta = $result instanceof RenderedContentWithFrontMatter ? $result->getFrontMatter() : [];

        return ['title' => $meta['title'] ?? ucfirst($slug), 'html' => $result->getContent()];
    }
}
