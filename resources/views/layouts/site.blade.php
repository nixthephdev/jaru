@php
    $name = setting('cafe_name');
    $site = rtrim(setting('site_url') ?: 'https://jarucafe.shop/', '/');
    $logo = setting('logo');
    $cover = setting('cover');
    $pageTitle = ($title ?? '') === '' ? "$name · ".setting('tagline') : "$title · $name";
    $desc = ($description ?? '') ?: setting('tagline').' · '.$name.', '.setting('address');
    $nav = ['' => 'Home', 'menu' => 'Menu', 'about' => 'About', 'visit' => 'Visit'];
    $logoUrl = $logo ? \App\Support\Photos::url($logo, small: true) : '';
@endphp
<!doctype html>
<html lang="en" class="js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $desc }}">
<meta name="theme-color" content="#f8f5ef">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ ($title ?? '') === '' ? $name : $pageTitle }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $site.'/'.($active === 'home' ? '' : $active) }}">
@if ($cover || $logo)<meta property="og:image" content="{{ $site.'/uploads/'.rawurlencode($cover ?: $logo) }}">@endif
<meta name="twitter:card" content="summary_large_image">
@if ($logoUrl)<link rel="icon" href="{{ $logoUrl }}"><link rel="apple-touch-icon" href="{{ $logoUrl }}">@endif
@vite(['resources/css/site.css', 'resources/js/site.js'])
<noscript><style>.reveal{opacity:1!important;transform:none!important}</style></noscript>
</head>
<body class="page-{{ $active }} min-h-screen">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-4 focus:py-2 focus:text-ivory">Skip to content</a>

{{-- On the menu page the header scrolls away on phones, so the pinned search bar does not eat the screen. --}}
<header data-site-head class="{{ $active === 'menu' ? 'md:sticky' : 'sticky' }} top-0 z-40 border-b border-line bg-ivory/85 backdrop-blur-xl backdrop-saturate-150">
  <div class="wrap flex h-[4.25rem] items-center justify-between gap-3 md:h-20">
    <a class="flex items-center gap-3 no-underline" href="{{ route('home') }}">
      @if ($logoUrl)<img src="{{ $logoUrl }}" alt="" width="44" height="44" class="size-10 rounded-full bg-white object-cover ring-1 ring-line md:size-11">@endif
      <span class="hidden font-display text-[1.35rem] font-[420] tracking-[-0.01em] min-[400px]:inline">{{ $name }}</span>
    </a>
    <nav class="flex items-center gap-0.5 sm:gap-2" aria-label="Main">
      @foreach ($nav as $slug => $label)
        @php $on = $active === $slug || ($slug === '' && $active === 'home'); @endphp
        <a href="{{ url($slug) }}" @if ($on) aria-current="page" @endif
           class="relative px-2.5 py-2 text-[0.7rem] font-bold uppercase tracking-[0.18em] no-underline transition-colors duration-300 sm:px-3 {{ $on ? 'text-ink after:absolute after:inset-x-2.5 after:-bottom-0.5 after:h-px after:bg-olive sm:after:inset-x-3' : 'text-muted hover:text-ink' }}">{{ $label }}</a>
      @endforeach
    </nav>
  </div>
</header>

<div id="main">
@yield('content')
</div>

<footer class="bg-oak text-ivory {{ $active === 'menu' ? '' : 'mt-24 md:mt-32' }}">
  @unless ($active === 'menu')
  {{-- Closing call to action --}}
  <div class="wrap border-b border-ivory/10 py-20 text-center md:py-28">
    <p class="eyebrow eyebrow-line !text-glow">Today at {{ $name }}</p>
    <h2 class="mx-auto mt-6 max-w-2xl text-[clamp(2.25rem,5.5vw,3.75rem)]">Craving a cup?</h2>
    <p class="mx-auto mt-4 max-w-md text-lg text-ivory/70">Take a look at what we are serving today.</p>
    <a class="btn btn-light mt-9" href="{{ url('menu') }}">View the menu</a>
  </div>
  @endunless

  <div class="wrap grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-[1.2fr_1fr_1fr_1fr] lg:gap-12">
    <div>
      @if ($logoUrl)<img src="{{ $logoUrl }}" alt="" width="56" height="56" loading="lazy" class="size-14 rounded-full bg-white object-cover">@endif
      <p class="mt-4 font-display text-2xl">{{ $name }}</p>
      @if (setting('tagline'))<p class="mt-1 max-w-xs text-ivory/60">{{ setting('tagline') }}</p>@endif
    </div>
    <div>
      <p class="eyebrow mb-4 !text-glow">Visit</p>
      @if (setting('address'))<p class="text-ivory/85">{{ setting('address') }}</p>@endif
      @if (setting('landmark'))<p class="text-ivory/55">{{ setting('landmark') }}</p>@endif
      <p class="mt-4"><a class="link !text-ivory hover:!text-glow" href="{{ maps_link() }}" rel="noopener">Directions <x-site-icon name="arrow"/></a></p>
    </div>
    @if (setting_lines('hours'))
    <div>
      <p class="eyebrow mb-4 !text-glow">Hours</p>
      @foreach (setting_lines('hours') as $h)<p class="text-ivory/85">{{ $h }}</p>@endforeach
    </div>
    @endif
    <div>
      <p class="eyebrow mb-4 !text-glow">Contact</p>
      @if (setting('phone'))<p><a class="text-ivory/85 hover:text-ivory" href="{{ tel(setting('phone')) }}">{{ setting('phone') }}</a></p>@endif
      @if (setting('email'))<p><a class="break-all text-ivory/85 hover:text-ivory" href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></p>@endif
      <p class="mt-4 flex gap-2.5">
        @if (setting('instagram'))<a class="grid size-10 place-items-center rounded-full border border-ivory/20 transition-colors duration-300 hover:border-glow hover:text-glow" href="{{ setting('instagram') }}" rel="noopener" aria-label="Instagram"><x-site-icon name="ig"/></a>@endif
        @if (setting('facebook'))<a class="grid size-10 place-items-center rounded-full border border-ivory/20 transition-colors duration-300 hover:border-glow hover:text-glow" href="{{ setting('facebook') }}" rel="noopener" aria-label="Facebook"><x-site-icon name="fb"/></a>@endif
      </p>
    </div>
  </div>
  <div class="border-t border-ivory/10">
    <div class="wrap flex flex-wrap items-center justify-between gap-3 py-6 text-xs tracking-wide text-ivory/50">
      <p>© {{ date('Y') }} {{ $name }} · Naga City</p>
      <nav class="flex gap-6 uppercase tracking-[0.18em]" aria-label="Footer"><a class="hover:text-ivory" href="{{ url('menu') }}">Menu</a><a class="hover:text-ivory" href="{{ url('about') }}">About</a><a class="hover:text-ivory" href="{{ url('visit') }}">Visit</a><a class="inline-flex items-center gap-1.5 hover:text-ivory" href="{{ url('admin') }}" rel="nofollow"><svg class="i" style="width:.85rem;height:.85rem" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Staff</a></nav>
    </div>
  </div>
</footer>
</body>
</html>
