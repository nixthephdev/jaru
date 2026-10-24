@php $story = setting_lines('about_text'); $cover = setting('cover'); $second = setting('hero'); @endphp
@extends('layouts.site', ['title' => 'Our story', 'active' => 'about', 'description' => implode(' ', array_slice($story, 0, 2))])

@section('content')
<main>
  <x-page-head :eyebrow="setting('tagline')" title="Our story" />

  {{-- The story beside the room it happens in --}}
  <section class="wrap grid items-center gap-12 md:grid-cols-[0.9fr_1.1fr] md:gap-16 lg:gap-24">
    <div class="relative mx-auto w-[min(76%,20rem)] md:w-full md:max-w-[24rem]">
      <span class="arch-echo" aria-hidden="true"></span>
      <div class="arch relative aspect-[4/5] bg-sand shadow-lift">
        @if ($cover)
        <img class="size-full object-cover" src="{{ \App\Support\Photos::url($cover) }}" alt="Inside {{ setting('cafe_name') }}, a café in {{ \App\Support\Seo::city() }}" width="1200" height="1500">
        @else
        <x-photo-placeholder label="Photo coming soon" />
        @endif
      </div>
    </div>
    <div class="max-w-xl">
      @foreach ($story as $p)
        @if ($loop->first)
        <p class="font-display text-[clamp(1.6rem,3vw,2.1rem)] font-[320] leading-snug">{{ $p }}</p>
        @else
        <p class="mt-6 text-lg text-muted">{{ $p }}</p>
        @endif
      @endforeach
      <a class="link mt-10" href="{{ url('menu') }}">See what we serve <x-site-icon name="arrow"/></a>
    </div>
  </section>

  @if ($second && $second !== $cover)
  <div class="wrap reveal mt-24 md:mt-32">
    <img class="aspect-[16/10] w-full rounded-[1.5rem] bg-sand object-cover md:aspect-[21/9]" src="{{ \App\Support\Photos::url($second) }}" alt="The counter at {{ setting('cafe_name') }}, {{ \App\Support\Seo::city() }}" loading="lazy" width="1200" height="750">
  </div>
  @endif

  {{-- A few things from the menu --}}
  @if ($photos->isNotEmpty())
  <section class="wrap reveal mt-24 md:mt-32">
    <header class="mb-10 flex flex-wrap items-end justify-between gap-x-6 gap-y-4 md:mb-14">
      <div>
        <p class="eyebrow eyebrow-line">Made to order</p>
        <h2 class="mt-4 text-[clamp(2.1rem,4.5vw,3.25rem)]">From our counter</h2>
      </div>
      <a class="link" href="{{ url('menu') }}">Full menu <x-site-icon name="arrow"/></a>
    </header>
    <ul class="grid grid-cols-2 gap-x-5 gap-y-8 md:grid-cols-3 md:gap-x-8 md:gap-y-12">
      @foreach ($photos as $p)
      <li>
        <a class="group block no-underline" href="{{ url('menu') }}#i{{ $p->id }}">
          <span class="block aspect-square overflow-hidden rounded-[1.25rem] bg-sand">
            <img class="size-full object-cover transition duration-[1.2s] group-hover:scale-[1.04]" src="{{ $p->photoUrl(true) }}" alt="{{ \App\Support\Seo::dishAlt($p->name) }}" loading="lazy" decoding="async" width="480" height="480">
          </span>
          <span class="mt-3 block text-[0.66rem] font-bold uppercase tracking-[0.2em] text-muted">{{ $p->category->name }}</span>
          <span class="mt-0.5 block font-display text-lg leading-tight transition-colors group-hover:text-olive md:text-xl">{{ $p->name }}</span>
        </a>
      </li>
      @endforeach
    </ul>
  </section>
  @endif
</main>
@endsection
