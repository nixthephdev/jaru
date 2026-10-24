import { createApp } from 'vue';
import MenuApp from './MenuApp.vue';

// Menu page: the server renders a plain list (for Google and slow phones); Vue replaces it with the interactive menu.
const mount = document.getElementById('menu-app');
if (mount) {
    const cats = JSON.parse(document.getElementById('menu-data').textContent);
    createApp(MenuApp, { cats, altSuffix: mount.dataset.altSuffix || '' }).mount(mount);
}

// Google Analytics, only when the owner has set an ID in Settings (the page then carries <meta name="ga-id">).
const ga = document.querySelector('meta[name="ga-id"]')?.content;
if (ga) {
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', ga);
}

// Sections ease in as they scroll into view.
document.documentElement.classList.add('js');
const io = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { rootMargin: '0px 0px -8% 0px' });
document.querySelectorAll('.reveal').forEach((el) => io.observe(el));

// Sticky offsets: the menu's search bar sits right under the site header.
const head = document.querySelector('[data-site-head]');
if (head) {
    const set = () => document.documentElement.style.setProperty('--head-h', getComputedStyle(head).position === 'sticky' ? head.offsetHeight + 'px' : '0px');
    set();
    addEventListener('resize', set);
}

// All public pages: offline cache (sw.js lives at the site root so it covers every page).
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}
