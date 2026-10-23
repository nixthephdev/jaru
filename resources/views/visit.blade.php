@php
    $embed = 'https://www.google.com/maps?output=embed&q='.rawurlencode(setting('map_query') ?: setting('cafe_name').', '.setting('address'));
    $hours = setting_lines('hours');
    $phone = setting('phone');
    // "Sunday – Thursday: 10 AM – 10 PM" becomes a day column and a time column; lines without a colon span both.
    $rows = array_map(fn ($h) => str_contains($h, ': ') ? explode(': ', $h, 2) : [$h, null], $hours);
@endphp
@extends('layouts.site', ['title' => 'Visit', 'active' => 'visit', 'description' => 'Find '.setting('cafe_name').' at '.setting('address').'. Opening hours, directions and contact.'])

@section('content')
<main>
  <x-page-head eyebrow="We'd love to see you" title="Visit us" />

  <section class="wrap grid gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16">
    <div class="divide-y divide-line border-y border-line">
      <div class="py-8">
        <p class="eyebrow flex items-center gap-2"><x-site-icon name="pin"/> Address</p>
        <p class="mt-3 font-display text-2xl leading-snug">{{ setting('address') }}</p>
        @if (setting('landmark'))<p class="mt-1 text-muted">{{ setting('landmark') }}</p>@endif
        <a class="btn btn-sm mt-6" href="{{ maps_link() }}" rel="noopener">Get directions</a>
      </div>

      <div class="py-8">
        <p class="eyebrow flex items-center gap-2"><x-site-icon name="clock"/> Opening hours</p>
        @if ($rows)
        <table class="mt-3 w-full text-left">
          <caption class="sr-only">Opening hours</caption>
          <tbody>
            @foreach ($rows as [$day, $time])
            <tr>
              @if ($time !== null)
              <th scope="row" class="py-1.5 pr-4 align-baseline font-normal">{{ $day }}</th>
              <td class="py-1.5 text-right align-baseline font-semibold tabular-nums">{{ $time }}</td>
              @else
              <td colspan="2" class="py-1.5 text-muted">{{ $day }}</td>
              @endif
            </tr>
            @endforeach
          </tbody>
        </table>
        @else
        <p class="mt-3 text-muted">Opening hours will be posted soon.</p>
        @endif
      </div>

      @if ($phone || setting('email') || setting('facebook') || setting('instagram'))
      <div class="py-8">
        <p class="eyebrow flex items-center gap-2"><x-site-icon name="phone"/> Contact</p>
        <div class="mt-3 space-y-1">
          @if ($phone)<p><a class="font-display text-2xl hover:text-olive" href="{{ tel($phone) }}">{{ $phone }}</a></p>@endif
          @if (setting('email'))<p><a class="break-all text-muted hover:text-ink" href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></p>@endif
        </div>
        @if (setting('instagram') || setting('facebook'))
        <p class="mt-5 flex flex-wrap gap-6">
          @if (setting('instagram'))<a class="link" href="{{ setting('instagram') }}" rel="noopener">Instagram <x-site-icon name="arrow"/></a>@endif
          @if (setting('facebook'))<a class="link" href="{{ setting('facebook') }}" rel="noopener">Facebook <x-site-icon name="arrow"/></a>@endif
        </p>
        @endif
      </div>
      @endif
    </div>

    <div class="overflow-hidden rounded-[1.5rem] bg-sand">
      <iframe class="block h-[24rem] w-full border-0 [filter:grayscale(0.35)_sepia(0.12)] lg:h-full lg:min-h-[32rem]" src="{{ $embed }}" title="Map to {{ setting('cafe_name') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </section>
</main>
@endsection
