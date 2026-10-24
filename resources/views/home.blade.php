@extends('layouts.site', ['title' => '', 'active' => 'home'])

@php
    $story = setting_lines('about_text');
    $cover = setting('cover');
    $second = setting('hero');
    $hours = setting_lines('hours');
    $photo = fn (?string $f, bool $sm = false) => \App\Support\Photos::url((string) $f, $sm);
@endphp

@section('content')
<main>
  {{-- Hero: the words on the left, the one arch photo on the right --}}
  <section class="wrap grid items-center gap-14 pb-20 pt-12 md:grid-cols-[1.1fr_0.9fr] md:gap-16 md:pb-28 md:pt-20 lg:gap-24">
    <div class="text-center md:text-left">
      {{-- The label is part of the h1, so the main heading names the café and the town for search engines. --}}
      <h1>
        <span class="eyebrow eyebrow-line font-sans">{{ setting('cafe_name') }}@if (\App\Support\Seo::city() !== '') · Café in {{ \App\Support\Seo::city() }}@endif</span>
        <span class="mt-6 block text-[clamp(2.75rem,6.4vw,5.25rem)] leading-[1.02]">{{ setting('tagline') ?: setting('cafe_name') }}</span>
      </h1>
      @if ($story)<p class="mx-auto mt-6 max-w-md text-lg text-muted md:mx-0">{{ $story[0] }}</p>@endif
      <div class="mt-10 flex flex-wrap justify-center gap-3 md:justify-start">
        <a class="btn" href="{{ url('menu') }}">View the menu</a>
        <a class="btn btn-line" href="{{ url('visit') }}">Find us</a>
      </div>
      @if ($hours)
      <p class="mt-10 inline-flex items-center gap-2.5 text-sm text-muted"><span class="size-1.5 rounded-full bg-olive"></span>{{ $hours[0] }}</p>
      @endif
    </div>

    <div class="relative mx-auto w-[min(76%,20rem)] md:w-full md:max-w-[25rem]">
      <span class="arch-echo" aria-hidden="true"></span>
      <div class="arch relative aspect-[4/5] bg-sand shadow-lift">
        @if ($cover)
        <img class="size-full object-cover" src="{{ $photo($cover) }}" alt="Inside {{ setting('cafe_name') }}, a café in {{ \App\Support\Seo::city() }}" width="1200" height="1500" fetchpriority="high">
        @else
        <x-photo-placeholder label="Photo coming soon" />
        @endif
      </div>
    </div>
  </section>

  {{-- Quick info: three columns divided by hairlines --}}
  <section class="reveal border-y border-line bg-linen" aria-label="Opening hours and contact">
    <div class="wrap grid divide-y divide-line md:grid-cols-3 md:divide-x md:divide-y-0">
      @if ($hours)
      <div class="py-8 md:py-10 md:pr-10">
        <p class="eyebrow flex items-center gap-2"><x-site-icon name="clock"/> Opening hours</p>
        <div class="mt-3 space-y-0.5">@foreach ($hours as $h)<p>{{ $h }}</p>@endforeach</div>
      </div>
      @endif
      <a class="group block py-8 no-underline md:px-10 md:py-10" href="{{ maps_link() }}" rel="noopener">
        <p class="eyebrow flex items-center gap-2"><x-site-icon name="pin"/> Find us</p>
        <p class="mt-3 transition-colors group-hover:text-olive">{{ setting('address') }}</p>
        @if (setting('landmark'))<p class="text-sm text-muted">{{ setting('landmark') }}</p>@endif
      </a>
      @if (setting('phone'))
      <a class="group block py-8 no-underline md:py-10 md:pl-10" href="{{ tel(setting('phone')) }}">
        <p class="eyebrow flex items-center gap-2"><x-site-icon name="phone"/> Call us</p>
        <p class="mt-3 transition-colors group-hover:text-olive">{{ setting('phone') }}</p>
        @if (setting('email'))<p class="break-all text-sm text-muted">{{ setting('email') }}</p>@endif
      </a>
      @endif
    </div>
  </section>

  {{-- Favourites: swipe on phones, four across on desktop --}}
  @if ($popular->isNotEmpty())
  <section class="reveal mt-24 md:mt-32">
    <div class="wrap">
      <header class="mb-10 flex flex-wrap items-end justify-between gap-x-6 gap-y-4 md:mb-14">
        <div>
          <p class="eyebrow eyebrow-line">Customer favourites</p>
          <h2 class="mt-4 text-[clamp(2.1rem,4.5vw,3.25rem)]">Our best-loved picks</h2>
        </div>
        <a class="link" href="{{ url('menu') }}">Full menu <x-site-icon name="arrow"/></a>
      </header>
    </div>
    <ul class="scrollbar-none flex snap-x scroll-px-5 gap-5 overflow-x-auto px-5 pb-2 sm:scroll-px-8 sm:px-8 md:mx-auto md:grid md:max-w-[76rem] md:grid-cols-4 md:gap-8 md:overflow-visible lg:px-12">
      @foreach ($popular as $f)
      <li class="w-[62%] max-w-60 flex-none snap-start md:w-auto md:max-w-none">
        <a class="group block no-underline" href="{{ url('menu') }}#i{{ $f->id }}">
          <span class="block aspect-[4/5] overflow-hidden rounded-[1.25rem] bg-sand">
            @if ($f->photo !== '')<img class="size-full object-cover transition duration-[1.2s] ease-[var(--ease-out-soft)] group-hover:scale-[1.04]" src="{{ $f->photoUrl(true) }}" alt="{{ \App\Support\Seo::dishAlt($f->name) }}" loading="lazy" decoding="async" width="480" height="600">@else<x-photo-placeholder />@endif
          </span>
          <span class="mt-4 block text-[0.66rem] font-bold uppercase tracking-[0.2em] text-muted">{{ $f->category->name }}</span>
          <span class="mt-1 flex items-baseline justify-between gap-3">
            <span class="font-display text-xl leading-tight transition-colors group-hover:text-olive">{{ $f->name }}</span>
            @if ($f->fromPrice())<span class="flex-none text-sm font-semibold tabular-nums">{{ $f->fromPrice() }}</span>@endif
          </span>
        </a>
      </li>
      @endforeach
    </ul>
  </section>
  @endif

  {{-- Story: the counter photo and a few words --}}
  @if ($story)
  <section class="wrap reveal mt-24 grid items-center gap-12 md:mt-32 md:grid-cols-2 md:gap-16 lg:gap-24">
    <div class="aspect-[4/5] w-full overflow-hidden rounded-[1.5rem] bg-sand md:order-1">
      @if ($second ?: $cover)
      <img class="size-full object-cover" src="{{ $photo($second ?: $cover) }}" alt="The counter at {{ setting('cafe_name') }}, {{ \App\Support\Seo::city() }}" loading="lazy" width="1200" height="1500">
      @else
      <x-photo-placeholder label="Photo coming soon" />
      @endif
    </div>
    <div class="md:order-2">
      <p class="eyebrow eyebrow-line">Our story</p>
      <h2 class="mt-5 text-[clamp(2.1rem,4.5vw,3.25rem)]">{{ setting('cafe_name') }}</h2>
      <p class="mt-6 font-display text-[1.6rem] font-[320] italic leading-snug text-ink/90">{{ $story[0] }}</p>
      @foreach (array_slice($story, 1, 2) as $p)<p class="mt-5 text-lg text-muted">{{ $p }}</p>@endforeach
      <a class="link mt-9" href="{{ url('about') }}">Read our story <x-site-icon name="arrow"/></a>
    </div>
  </section>
  @endif

  {{-- Coming soon: a dark band, the one moment of drama on the page --}}
  @if ($soon->isNotEmpty())
  <section class="reveal mt-24 md:mt-32">
    <div class="wrap">
      <div class="grid gap-10 rounded-[2rem] bg-ink px-6 py-14 text-ivory sm:px-10 md:grid-cols-[0.8fr_1.2fr] md:gap-14 md:px-14 md:py-20">
        <div>
          <p class="eyebrow eyebrow-line !text-glow">Something new is brewing</p>
          <h2 class="mt-5 text-[clamp(2.1rem,4.5vw,3.25rem)]">Coming soon</h2>
          <p class="mt-4 max-w-xs text-ivory/65">A first look at what we are working on next.</p>
        </div>
        <ul class="divide-y divide-ivory/12 border-y border-ivory/12">
          @foreach ($soon as $s)
          <li>
            <a class="group flex items-center gap-5 py-5 no-underline" href="{{ url('menu') }}#c{{ $s->id }}">
              @if ($s->items->first())<img class="size-16 flex-none rounded-full object-cover ring-1 ring-ivory/20 md:size-20" src="{{ $s->items->first()->photoUrl(true) }}" alt="{{ \App\Support\Seo::dishAlt($s->items->first()->name) }}" loading="lazy" width="480" height="480">@else<span class="block size-16 flex-none overflow-hidden rounded-full md:size-20"><x-photo-placeholder /></span>@endif
              <span class="min-w-0 flex-1">
                <span class="block font-display text-2xl transition-colors group-hover:text-glow">{{ $s->name }}</span>
                @if ($s->note !== '')<span class="block text-sm text-ivory/60">{{ $s->note }}</span>@endif
                <span class="mt-1 block text-[0.66rem] font-bold uppercase tracking-[0.2em] text-glow/90">{{ $s->n }} {{ $s->n === 1 ? 'item' : 'items' }}</span>
              </span>
              <span class="text-ivory/50 transition-transform duration-500 group-hover:translate-x-1 group-hover:text-glow"><x-site-icon name="arrow"/></span>
            </a>
          </li>
          @endforeach
        </ul>
      </div>
    </div>
  </section>
  @endif
</main>
@endsection
