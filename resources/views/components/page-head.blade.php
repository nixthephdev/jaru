{{-- Title block at the top of the inner pages: tracked label, light serif title, optional line below. --}}
@props(['eyebrow' => '', 'title'])
<section class="wrap pb-10 pt-14 text-center md:pb-14 md:pt-20">
  @if ($eyebrow !== '')<p class="eyebrow eyebrow-line">{{ $eyebrow }}</p>@endif
  <h1 class="mx-auto mt-5 max-w-3xl text-[clamp(2.75rem,7vw,4.75rem)]">{{ $title }}</h1>
  @if ($slot->isNotEmpty())<div class="mx-auto mt-5 max-w-xl text-lg text-muted">{{ $slot }}</div>@endif
</section>
