<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Support\Money;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SiteController extends Controller
{
    public function home(): View
    {
        $popular = Item::query()->with('category')
            ->where('featured', true)->where('hidden', false)
            ->whereHas('category', fn ($q) => $q->where('status', 'active'))
            ->get()->sortBy([['category.sort', 'asc'], ['sort', 'asc']]);

        // The desktop grid is four across, so show a full row or two (4 or 8); fewer than four are all shown.
        $popular = $popular->take($popular->count() >= 4 ? intdiv(min($popular->count(), 8), 4) * 4 : $popular->count());

        $soon = Category::where('status', 'soon')
            ->withCount(['items as n' => fn ($q) => $q->where('hidden', false)])
            ->with(['items' => fn ($q) => $q->where('hidden', false)->where('photo', '!=', '')])
            ->orderBy('sort')->get()
            ->filter(fn ($c) => $c->n > 0);

        return view('home', compact('popular', 'soon'));
    }

    public function menu(): View
    {
        $cats = Category::where('status', '!=', 'hidden')
            ->with(['items' => fn ($q) => $q->where('hidden', false)])
            ->orderBy('sort')->orderBy('id')->get()
            ->filter(fn ($c) => $c->items->isNotEmpty())->values();

        // Handed to the Vue menu as JSON; the Blade view also renders a plain list for Google and slow phones.
        $menu = $cats->map(fn ($c) => [
            'id' => $c->id, 'name' => $c->name, 'note' => $c->note, 'status' => $c->status,
            'items' => $c->items->map(fn ($i) => [
                'id' => $i->id, 'name' => $i->name, 'description' => $i->description,
                'photo' => $i->photo !== '', 'photoSm' => $i->photoUrl(true), 'photoFull' => $i->photoUrl(),
                'sold_out' => $i->sold_out, 'featured' => $i->featured,
                'prices' => array_map(fn ($p) => ['label' => $p['label'], 'cents' => $p['cents'], 'text' => Money::peso($p['cents'])], $i->pricedRows()),
            ])->values(),
        ])->values();

        return view('menu', compact('cats', 'menu'));
    }

    public function about(): View
    {
        // A few menu photos make a small gallery.
        $photos = Item::query()->with('category')
            ->where('photo', '!=', '')->where('hidden', false)
            ->whereHas('category', fn ($q) => $q->where('status', 'active'))
            ->get()->sortBy([['featured', 'desc'], ['category.sort', 'asc'], ['sort', 'asc']])->take(6);

        return view('about', compact('photos'));
    }

    public function visit(): View
    {
        return view('visit');
    }

    /** The public pages for search engines, dated by the last menu change. */
    public function sitemap(): Response
    {
        $site = Seo::site();
        $last = collect([Item::max('updated_at'), Category::max('updated_at')])->filter()->max();
        $date = $last ? Carbon::parse($last)->toDateString() : now()->toDateString();
        $pages = ['/' => '1.0', '/menu' => '0.9', '/visit' => '0.8', '/about' => '0.6', '/privacy' => '0.2'];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($pages as $path => $priority) {
            $xml .= '  <url><loc>'.e($site.$path).'</loc>'
                ."<lastmod>$date</lastmod><priority>$priority</priority></url>\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
