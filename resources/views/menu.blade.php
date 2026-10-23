@extends('layouts.site', ['title' => 'Menu', 'active' => 'menu', 'description' => 'Menu of '.setting('cafe_name').', '.setting('address').'. Coffee, matcha, non-coffee drinks, meals and cakes.'])

@section('content')
<main>
  <x-page-head eyebrow="Coffee, matcha & comfort food" title="The menu" />

  @if (\App\Models\Setting::menuIsPreview())
  <div class="wrap -mt-2 mb-8 md:mb-10" role="note">
    <p class="mx-auto flex max-w-xl items-start justify-center gap-3 border-y border-line py-3.5 text-center text-sm text-muted">
      <span><strong class="font-bold text-ink">Preview menu.</strong> Our final menu, photos and prices are coming soon.</span>
    </p>
  </div>
  @endif

  <script type="application/json" id="menu-data">@json($menu)</script>

  {{-- Plain list for Google and slow connections; the Vue menu replaces it once it loads. --}}
  <div id="menu-app">
    <div class="wrap pb-16">
      @foreach ($cats as $c)
      <section class="mt-10" id="c{{ $c->id }}">
        <h2 class="border-b border-ink pb-3 text-3xl">{{ $c->name }}@if ($c->status === 'soon') <span class="tag align-middle">Coming soon</span>@endif</h2>
        @if ($c->note !== '')<p class="mt-2 text-sm text-muted">{{ $c->note }}</p>@endif
        <ul class="divide-y divide-line">
          @foreach ($c->items as $it)
          <li id="i{{ $it->id }}" class="py-4">
            <h3 class="text-xl">{{ $it->name }}</h3>
            @if ($it->description !== '')<p class="text-sm text-muted">{{ $it->description }}</p>@endif
            @if ($c->status !== 'soon')
              <p class="mt-1 font-semibold">{{ $it->priceText() }}@if ($it->sold_out) <span class="tag tag-sold">Sold out · Ubos na</span>@endif</p>
            @endif
          </li>
          @endforeach
        </ul>
      </section>
      @endforeach
    </div>
  </div>
</main>
@endsection
