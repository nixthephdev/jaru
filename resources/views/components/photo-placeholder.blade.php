{{-- Stand-in for a photo the café has not sent yet. Fill the parent's size; pass a label to spell it out. --}}
@props(['label' => null])
<span {{ $attributes->class('flex size-full flex-col items-center justify-center gap-3 bg-sand text-olive/55') }} role="img" aria-label="Photo coming soon">
  <svg class="size-[26%] min-h-6 min-w-6 max-h-12 max-w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 10h11v4a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5v-4z"/><path d="M16 11h1.5a2.5 2.5 0 0 1 0 5H16"/><path d="M9 3.5c-1 1.4 1 2.6 0 4M13 3.5c-1 1.4 1 2.6 0 4"/></svg>
  @if ($label)<span class="text-[0.66rem] font-bold uppercase tracking-[0.22em] text-muted">{{ $label }}</span>@endif
</span>
