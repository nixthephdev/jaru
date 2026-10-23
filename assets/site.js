// All public pages: offline cache (sw.js lives at the site root so it covers every page).
if ('serviceWorker' in navigator) {
  var root = document.querySelector('.brand').getAttribute('href').replace(/\/?$/, '/');
  navigator.serviceWorker.register(root + 'sw.js').catch(function () {});
}
