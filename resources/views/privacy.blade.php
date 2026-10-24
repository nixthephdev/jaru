@php
    $name = setting('cafe_name');
    $analytics = \App\Support\Seo::analyticsId(setting('ga_id')) !== '';
    $contact = array_filter([setting('email'), setting('phone')]);
@endphp
@extends('layouts.site', ['title' => 'Privacy', 'active' => 'privacy', 'description' => 'How the '.$name.' website handles your information: what it collects, the cookies it uses and who to contact.'])

@section('content')
<main>
  <x-page-head eyebrow="Your information" title="Privacy" />

  {{-- Plain words, one short section per thing the site does. The Analytics part shows only while an Analytics ID is set. --}}
  <article class="wrap">
    <div class="mx-auto max-w-2xl divide-y divide-line border-y border-line text-muted [&_h2]:text-2xl [&_h2]:text-ink [&_a]:text-ink [&_a]:underline [&_a]:underline-offset-4 [&_a:hover]:text-olive [&_section]:space-y-3 [&_section]:py-8">
      <section>
        <h2>What this website collects</h2>
        <p>Nothing that identifies you. You can read the menu and find us without an account, and we never ask for your name, phone number or payment details here.</p>
      </section>

      @if ($analytics)
      <section>
        <h2>Visit statistics</h2>
        <p>We use Google Analytics to count visits, so we know which pages are useful. It records things like the pages you open, your type of device and browser, and your approximate city. It does not tell us who you are.</p>
        <p>Google Analytics stores small files (cookies) on your device to tell one visit from the next. You can block or delete cookies in your browser settings at any time, or install Google's <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener">opt-out add-on</a>. Google explains how it handles this data in its <a href="https://policies.google.com/privacy" rel="noopener">privacy policy</a>.</p>
      </section>
      @endif

      <section>
        <h2>The map</h2>
        <p>The Visit page shows a Google Map so you can find us. Google may set its own cookies when the map loads, as described in its <a href="https://policies.google.com/privacy" rel="noopener">privacy policy</a>.</p>
      </section>

      <section>
        <h2>The menu on your phone</h2>
        <p>When you open the menu, your phone keeps a copy so it still works if the signal drops. That copy stays on your phone only, and clearing your browser's site data removes it.</p>
      </section>

      <section>
        <h2>Questions</h2>
        <p>You have rights over your personal information under the Data Privacy Act of 2012 (Republic Act No. 10173).@if ($contact) For any question about privacy, contact {{ $name }} at {!! collect($contact)->map(fn ($c) => '<a href="'.e(str_contains($c, '@') ? 'mailto:'.$c : tel($c)).'">'.e($c).'</a>')->implode(' or ') !!}.@endif</p>
        <p class="text-sm">Last updated October 24, 2026.</p>
      </section>
    </div>
  </article>
</main>
@endsection
