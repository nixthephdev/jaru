<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({ cats: { type: Array, required: true } });

const query = ref('');
const online = ref(navigator.onLine);
const active = ref('');
const showTop = ref(false);
const open = ref(null); // { item, cat } shown in the sheet
const sheet = ref(null);
const bar = ref(null);
const chips = ref(null);

const words = computed(() => query.value.toLowerCase().split(/\s+/).filter(Boolean));

// Search name, description and category; every word must match.
const visibleCats = computed(() => {
    if (!words.value.length) return props.cats;
    return props.cats
        .map((c) => ({
            ...c,
            items: c.items.filter((i) => {
                const hay = `${i.name} ${i.description} ${c.name}`.toLowerCase();
                return words.value.every((w) => hay.includes(w));
            }),
        }))
        .filter((c) => c.items.length);
});
const found = computed(() => visibleCats.value.reduce((n, c) => n + c.items.length, 0));

// Split text into plain and highlighted parts, so no HTML is ever injected.
function parts(text) {
    if (!words.value.length || !text) return [{ t: text, hit: false }];
    const source = words.value.map((w) => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
    const split = new RegExp('(' + source + ')', 'gi');
    const test = new RegExp('^(' + source + ')$', 'i');
    return text.split(split).filter((t) => t !== '').map((t) => ({ t, hit: test.test(t) }));
}

// Category tab styling, shared by the active/inactive states: a fine olive underline marks the current one.
const chip = (on) =>
    'relative flex-none py-3.5 text-[0.7rem] font-bold uppercase tracking-[0.18em] transition-colors duration-300 ' +
    (on ? 'text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-olive' : 'text-muted hover:text-ink');

// Prices show for open categories only; coming-soon ones have none yet.
const priced = (c) => c.status !== 'soon';
const soldOut = (i, c) => i.sold_out && priced(c);
const num = (n) => String(n + 1).padStart(2, '0');

const toTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

function barHeight() {
    return (bar.value?.offsetHeight || 0) + 8;
}

function go(id) {
    const el = document.getElementById(id);
    if (!el) return;
    window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - barHeight(), behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
}

function openItem(item, cat) {
    open.value = { item, cat };
    nextTick(() => sheet.value?.showModal());
    history.replaceState(null, '', '#i' + item.id);
}
function closeItem() {
    sheet.value?.close();
}
function onClose() {
    open.value = null;
    history.replaceState(null, '', location.pathname);
}
function backdrop(e) {
    if (e.target === sheet.value) closeItem();
}

// Keep the active chip in view while scrolling through the menu.
let observer;
function watchSections() {
    observer?.disconnect();
    observer = new IntersectionObserver(
        (entries) => {
            const hit = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
            if (hit) active.value = hit.target.id;
        },
        { rootMargin: `-${barHeight() + 10}px 0px -65% 0px` }
    );
    document.querySelectorAll('[data-section]').forEach((s) => observer.observe(s));
}
watch(active, (id) => {
    chips.value?.querySelector(`[data-chip="${id}"]`)?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
});
watch(visibleCats, () => nextTick(watchSections));

// Deep links from the home page: /menu#i12 opens that item, /menu#c3 scrolls to a category.
function openFromHash() {
    const hash = location.hash.slice(1);
    if (/^i\d+$/.test(hash)) {
        for (const c of props.cats) {
            const item = c.items.find((i) => 'i' + i.id === hash);
            if (item) { nextTick(() => { go(hash); openItem(item, c); }); break; }
        }
    } else if (/^c\d+$/.test(hash)) {
        nextTick(() => go(hash));
    }
}

const onScroll = () => (showTop.value = window.scrollY > 900);
const setOnline = () => (online.value = navigator.onLine);

onMounted(() => {
    // Highlight the first category right away; scrolling takes over from here.
    if (props.cats[0]) active.value = 'c' + props.cats[0].id;
    document.documentElement.style.scrollPaddingTop = barHeight() + 'px';
    watchSections();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('online', setOnline);
    window.addEventListener('offline', setOnline);

    openFromHash();
    window.addEventListener('hashchange', openFromHash);
});
onBeforeUnmount(() => {
    observer?.disconnect();
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('hashchange', openFromHash);
    window.removeEventListener('online', setOnline);
    window.removeEventListener('offline', setOnline);
});
</script>

<template>
    <div>
        <!-- Sticky search + category tabs -->
        <div ref="bar" class="sticky top-[var(--head-h,0px)] z-30 border-b border-line bg-ivory/90 backdrop-blur-xl backdrop-saturate-150">
            <div class="wrap flex flex-col pt-3 md:flex-row md:items-center md:gap-8 md:pt-0">
                <label class="relative block md:order-2 md:w-72">
                    <span class="sr-only">Search the menu</span>
                    <svg class="i pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M16.5 16.5 21 21" /></svg>
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search drinks and food"
                        autocomplete="off"
                        enterkeyhint="search"
                        class="h-11 w-full rounded-full border border-transparent bg-linen pl-11 pr-4 text-[0.95rem] outline-none transition-colors duration-300 placeholder:text-muted focus:border-line focus:bg-white"
                    />
                </label>
                <nav ref="chips" aria-label="Menu categories" class="scrollbar-none flex min-w-0 flex-1 gap-7 overflow-x-auto pr-8 [mask-image:linear-gradient(to_right,black_calc(100%-2.5rem),transparent)] md:order-1">
                    <button v-for="c in visibleCats" :key="c.id" type="button" :data-chip="'c' + c.id" :class="chip(active === 'c' + c.id)" @click="go('c' + c.id)">{{ c.name }}</button>
                </nav>
            </div>
        </div>

        <div class="wrap pb-24 pt-4">
            <p v-if="!online" class="mt-6 border-y border-line py-3 text-center text-sm text-muted" role="status">You're offline. Showing the last saved menu.</p>
            <p v-if="words.length" class="mt-6 text-[0.7rem] font-bold uppercase tracking-[0.2em] text-muted" aria-live="polite">{{ found }} {{ found === 1 ? 'item' : 'items' }} found</p>

            <!-- Categories: a firm rule under each title, hairlines between dishes, like a printed menu -->
            <section v-for="(c, n) in visibleCats" :id="'c' + c.id" :key="c.id" data-section class="pt-12 md:pt-16">
                <header class="flex items-end justify-between gap-4 border-b border-ink pb-4">
                    <div>
                        <p class="font-display text-sm italic text-olive">{{ num(n) }}</p>
                        <h2 class="mt-1 text-[2rem] md:text-[2.6rem]">{{ c.name }}</h2>
                    </div>
                    <span v-if="c.status === 'soon'" class="tag mb-2">Coming soon</span>
                    <span v-else class="mb-2 flex-none text-[0.66rem] font-bold uppercase tracking-[0.2em] text-muted">{{ c.items.length }} {{ c.items.length === 1 ? 'item' : 'items' }}</span>
                </header>
                <p v-if="c.note" class="mt-3 text-sm text-muted">{{ c.note }}</p>

                <ul class="grid gap-x-14 lg:grid-cols-2">
                    <li v-for="i in c.items" :id="'i' + i.id" :key="i.id">
                        <button type="button" class="group flex w-full items-start gap-4 border-b border-line py-5 text-left sm:gap-5" :aria-label="i.name + ', details'" @click="openItem(i, c)">
                            <span class="block size-[4.5rem] flex-none overflow-hidden rounded-2xl bg-sand sm:size-20" :class="soldOut(i, c) && 'opacity-50 grayscale'">
                                <img v-if="i.photo" :src="i.photoSm" alt="" loading="lazy" decoding="async" width="480" height="480" class="size-full object-cover transition duration-700 group-hover:scale-105" />
                                <span v-else class="grid size-full place-items-center text-olive/50" aria-hidden="true">
                                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10h11v4a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5v-4z" /><path d="M16 11h1.5a2.5 2.5 0 0 1 0 5H16" /><path d="M9 3.5c-1 1.4 1 2.6 0 4M13 3.5c-1 1.4 1 2.6 0 4" /></svg>
                                </span>
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col pt-0.5">
                                <span class="flex items-baseline gap-3">
                                    <span class="font-display text-[1.28rem] leading-snug transition-colors duration-300 group-hover:text-olive" :class="soldOut(i, c) && 'text-muted'">
                                        <template v-for="(p, k) in parts(i.name)" :key="k"><mark v-if="p.hit">{{ p.t }}</mark><template v-else>{{ p.t }}</template></template>
                                    </span>
                                    <template v-if="priced(c) && i.prices.length === 1">
                                        <span class="leader" aria-hidden="true"></span>
                                        <span class="flex-none font-semibold tabular-nums"><small v-if="i.prices[0].label" class="mr-1.5 text-xs font-semibold text-muted">{{ i.prices[0].label }}</small>{{ i.prices[0].text }}</span>
                                    </template>
                                </span>
                                <span v-if="i.description" class="mt-1 line-clamp-2 text-sm leading-relaxed text-muted">
                                    <template v-for="(p, k) in parts(i.description)" :key="k"><mark v-if="p.hit">{{ p.t }}</mark><template v-else>{{ p.t }}</template></template>
                                </span>
                                <span v-if="priced(c) && i.prices.length > 1" class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                                    <span v-for="p in i.prices" :key="p.label + p.text" class="tabular-nums"><small v-if="p.label" class="mr-1.5 text-xs font-semibold text-muted">{{ p.label }}</small><span class="font-semibold">{{ p.text }}</span></span>
                                </span>
                                <span v-if="soldOut(i, c)" class="tag tag-sold mt-2.5 self-start">Sold out · Ubos na</span>
                            </span>
                        </button>
                    </li>
                </ul>
            </section>

            <div v-if="!found" class="py-20 text-center">
                <p class="font-display text-2xl">Nothing matches "{{ query }}"</p>
                <button type="button" class="link mt-5" @click="query = ''">Clear search</button>
            </div>
            <p class="mt-14 text-center text-[0.66rem] font-bold uppercase tracking-[0.2em] text-muted">Prices may change without notice</p>
        </div>

        <button
            v-show="showTop"
            type="button"
            aria-label="Back to top"
            class="fixed bottom-5 right-5 z-40 grid size-12 place-items-center rounded-full bg-ink text-ivory shadow-lift transition-colors duration-300 hover:bg-olive"
            @click="toTop"
        >
            <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6" /></svg>
        </button>

        <!-- Item sheet: bottom sheet on phones, centered card on larger screens -->
        <dialog
            ref="sheet"
            aria-labelledby="sh-name"
            class="sheet m-0 mt-auto max-h-[92dvh] w-full max-w-none overflow-y-auto rounded-t-[2rem] bg-ivory p-0 text-ink shadow-2xl backdrop:bg-ink/55 backdrop:backdrop-blur-sm md:m-auto md:w-[30rem] md:rounded-[2rem]"
            @close="onClose"
            @click="backdrop"
        >
            <template v-if="open">
                <span class="mx-auto mt-3 block h-1 w-10 rounded-full bg-ink/15 md:hidden" aria-hidden="true"></span>
                <button type="button" aria-label="Close" class="absolute right-4 top-4 z-10 grid size-10 place-items-center rounded-full border border-line bg-ivory/90 transition-colors hover:bg-ink hover:text-ivory" @click="closeItem">
                    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
                <div class="relative mx-auto mt-8 w-64 max-w-[64%]">
                    <span class="arch-echo" aria-hidden="true"></span>
                    <div class="arch relative aspect-[4/5] bg-sand">
                        <img v-if="open.item.photo" :src="open.item.photoFull" :alt="open.item.name" class="size-full object-cover" />
                        <span v-else class="flex size-full flex-col items-center justify-center gap-3 text-olive/55" role="img" aria-label="Photo coming soon">
                            <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 10h11v4a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5v-4z" /><path d="M16 11h1.5a2.5 2.5 0 0 1 0 5H16" /><path d="M9 3.5c-1 1.4 1 2.6 0 4M13 3.5c-1 1.4 1 2.6 0 4" /></svg>
                            <span class="text-[0.66rem] font-bold uppercase tracking-[0.22em] text-muted">Photo coming soon</span>
                        </span>
                    </div>
                </div>
                <div class="px-7 pb-10 pt-10 text-center">
                    <p class="eyebrow">{{ open.cat.name }}</p>
                    <h2 id="sh-name" class="mt-3 text-[2.4rem]">{{ open.item.name }}</h2>
                    <p v-if="open.item.description" class="mx-auto mt-3 max-w-xs text-muted">{{ open.item.description }}</p>
                    <ul v-if="priced(open.cat) && open.item.prices.length" class="mx-auto mt-7 max-w-[17rem] space-y-2.5 text-left">
                        <li v-for="p in open.item.prices" :key="p.label + p.text" class="flex items-baseline gap-3">
                            <span class="text-sm font-semibold text-muted">{{ p.label || 'Price' }}</span>
                            <span class="leader" aria-hidden="true"></span>
                            <span class="text-lg font-semibold tabular-nums">{{ p.text }}</span>
                        </li>
                    </ul>
                    <p v-else-if="!priced(open.cat)" class="tag mt-6">Coming soon</p>
                    <p v-else class="mt-6 text-[0.7rem] font-bold uppercase tracking-[0.2em] text-muted">Price coming soon</p>
                    <p v-if="soldOut(open.item, open.cat)" class="tag tag-sold mt-5">Sold out · Ubos na</p>
                </div>
            </template>
        </dialog>
    </div>
</template>
