{{-- Login and password pages: the café's cover photo beside the form on wide screens (hidden on phones). --}}
@if (! auth()->check() && setting('cover'))
<aside class="jaru-auth-photo" aria-hidden="true">
  <img src="{{ \App\Support\Photos::url(setting('cover')) }}" alt="">
  <div class="jaru-auth-photo-text">
    <p class="jaru-eyebrow">{{ setting('cafe_name') }} · Owner admin</p>
    <p class="jaru-auth-quote">{{ setting('tagline') ?: setting('cafe_name') }}</p>
  </div>
</aside>
@endif
