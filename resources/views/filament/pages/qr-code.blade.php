<x-filament-panels::page>
    <style>
        /* Cards match the website: ivory paper in a fine olive frame, a light serif name, the QR inside an arch like the café's doors, logo in the middle. */
        .qr-sheet { display: grid; gap: 12px; }
        .qr-sheet.n4, .qr-sheet.n8 { grid-template-columns: repeat(2, 1fr); }
        .qr-card {
            position: relative; overflow: hidden; border-radius: 18px; text-align: center; break-inside: avoid; padding: 28px 20px 24px;
            background: #f8f5ef; color: #2b2420; display: flex; flex-direction: column; align-items: center; justify-content: center;
            -webkit-print-color-adjust: exact; print-color-adjust: exact; box-shadow: 0 0 0 1px rgba(43,36,32,.12);
        }
        .qr-card::before { content: ''; position: absolute; inset: 9px; border: 1px solid rgba(93,98,52,.35); border-radius: 12px; pointer-events: none; }
        .qr-name { font: 380 1.9rem/1.05 'Fraunces Variable', Georgia, serif; letter-spacing: -.02em; margin: 0 0 16px; }
        .qr-arch { position: relative; width: 66%; max-width: 340px; background: #fff; border: 1px solid rgba(93,98,52,.4); border-radius: 50% 50% 18px 18px / 41% 41% 18px 18px; padding: 30% 9% 9%; } /* 1.21 times as tall as wide: 41% of the height is a half-circle top */
        .qr-arch-label { position: absolute; left: 0; right: 0; top: 14%; margin: 0; font: 700 .62rem 'Manrope Variable', system-ui, sans-serif; letter-spacing: .26em; text-transform: uppercase; color: #5d6234; }
        .qr-box { position: relative; }
        .qr svg { display: block; width: 100%; height: auto; }
        /* High error correction (H) lets the code survive this ~22% logo in the middle. */
        .qr-logo { position: absolute; left: 50%; top: 50%; width: 22%; aspect-ratio: 1; transform: translate(-50%, -50%); border-radius: 50%; object-fit: cover; background: #fff; box-shadow: 0 0 0 5px #fff; }
        .qr-cta { font: italic 340 1.35rem/1.2 'Fraunces Variable', Georgia, serif; margin: 16px 0 0; }
        .qr-url { font: 700 .72rem 'Manrope Variable', system-ui, sans-serif; color: #5d6234; margin: 6px 0 0; letter-spacing: .2em; text-transform: uppercase; }
        .qr-sheet.n1 .qr-card { max-width: 520px; width: 100%; margin: 0 auto; padding: 48px 28px 40px; }
        .qr-sheet.n1 .qr-card::before { inset: 14px; }
        .qr-sheet.n1 .qr-name { font-size: 3rem; margin-bottom: 26px; }
        .qr-sheet.n1 .qr-arch-label { font-size: .8rem; }
        .qr-sheet.n1 .qr-cta { font-size: 2rem; margin-top: 24px; }
        .qr-sheet.n1 .qr-url { font-size: 1rem; }
        /* Small cards: no room for the dome, so the frame is a plain rounded square. */
        .qr-sheet.n8 .qr-card { padding: 14px 10px 12px; }
        .qr-sheet.n8 .qr-card::before { inset: 6px; }
        .qr-sheet.n8 .qr-name { font-size: 1.15rem; margin-bottom: 8px; }
        .qr-sheet.n8 .qr-arch { border-radius: 12px; padding: 6%; }
        .qr-sheet.n8 .qr-arch-label { display: none; }
        .qr-sheet.n8 .qr-cta { font-size: .95rem; margin-top: 6px; }
        .qr-sheet.n8 .qr-url { font-size: .55rem; margin-top: 2px; }

        @media print {
            @page { size: A4; margin: 10mm; }
            body { background: #fff !important; }
            .fi-sidebar, .fi-sidebar-close-overlay, .fi-topbar, .fi-header, .fi-footer, .fi-notifications, .qr-noprint { display: none !important; }
            .fi-main-ctn, .fi-main, .fi-page-header-main-ctn { margin: 0 !important; padding: 0 !important; gap: 0 !important; max-width: none !important; min-height: 0 !important; }
            .qr-sheet { margin: 0; gap: 4mm; height: 277mm; }
            .qr-card { border-radius: 4mm; box-shadow: none; border: 1px solid #e4dbcd; }
            .qr-sheet.n1 { display: flex; align-items: center; justify-content: center; }
            .qr-sheet.n1 .qr-card { max-width: 150mm; height: 210mm; }
            .qr-sheet.n4 { grid-template-rows: repeat(2, 1fr); }
            .qr-sheet.n8 { grid-template-rows: repeat(4, 1fr); }
            .qr-sheet.n8 .qr-arch { width: 40%; }
            .qr-sheet.n4 .qr-arch { width: 58%; }
        }
    </style>
    <script src="{{ asset('js/qrcode.js') }}"></script>

    <div
        x-data="qrSheet({{ Js::from(['url' => $url, 'short' => $short, 'name' => $name, 'logo' => $logo]) }})"
        class="space-y-6"
    >
        <div class="qr-noprint space-y-4">
            <p class="text-sm text-gray-600">
                Points to <b x-text="cfg.url"></b>. Change it in
                <a href="{{ \App\Filament\Pages\Settings::getUrl() }}" class="text-primary-600 underline">Settings</a>.
            </p>

            <div class="flex flex-wrap gap-2">
                @foreach ([1 => 'Counter standee (1 per page)', 4 => 'Tables (4 per A4)', 8 => 'Small (8 per A4)'] as $k => $label)
                    <x-filament::button color="gray" x-bind:class="n === {{ $k }} ? 'ring-2 ring-primary-500' : ''" x-on:click="n = {{ $k }}">{{ $label }}</x-filament::button>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button icon="heroicon-o-printer" x-on:click="window.print()">Print</x-filament::button>
                <x-filament::button icon="heroicon-o-arrow-down-tray" color="gray" x-on:click="png()">Download QR as PNG</x-filament::button>
            </div>

            <p class="text-sm text-gray-500">Print tip: choose A4, scale 100%, margins "Default". Test-scan one card before printing many. The PNG is high resolution, for print shops.</p>
        </div>

        <div class="qr-sheet" x-bind:class="'n' + n">
            <template x-for="i in n" :key="i">
                <div class="qr-card">
                    <p class="qr-name" x-text="cfg.name"></p>
                    <div class="qr-arch">
                        <p class="qr-arch-label">Our menu</p>
                        <div class="qr-box">
                            <div class="qr" x-html="svg"></div>
                            @if ($logoSmall)<img src="{{ $logoSmall }}" alt="" class="qr-logo">@endif
                        </div>
                    </div>
                    <p class="qr-cta">Scan to see our menu</p>
                    <p class="qr-url" x-text="cfg.short"></p>
                </div>
            </template>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('qrSheet', (cfg) => {
                const INK = '#2b2420', PANEL = '#ffffff', IVORY = '#f8f5ef', OLIVE = '#5d6234';
                // Level H (30% recovery) so the logo covering the middle doesn't stop phones reading it.
                const qr = qrcode(0, 'H');
                qr.addData(cfg.url);
                qr.make();
                const count = qr.getModuleCount(), q = count + 4; // 2-module quiet zone
                let d = '';
                for (let r = 0; r < count; r++) for (let c = 0; c < count; c++) if (qr.isDark(r, c)) d += `M${c} ${r}h1v1h-1z`;
                const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 ${q} ${q}" shape-rendering="crispEdges" role="img" aria-label="QR code to ${cfg.url.replace(/[<>"&]/g, '')}"><rect x="-2" y="-2" width="${q}" height="${q}" fill="${PANEL}"/><path fill="${INK}" d="${d}"/></svg>`;

                const load = (src) => new Promise((ok) => { const i = new Image(); i.onload = () => ok(i); i.onerror = () => ok(null); i.src = src; });
                const roundRect = (x, X, Y, w, h, rad) => {
                    x.beginPath(); x.moveTo(X + rad, Y); x.arcTo(X + w, Y, X + w, Y + h, rad); x.arcTo(X + w, Y + h, X, Y + h, rad);
                    x.arcTo(X, Y + h, X, Y, rad); x.arcTo(X, Y, X + w, Y, rad); x.closePath();
                };
                // Arch: a half-circle dome on a rectangle with softly rounded bottom corners.
                const arch = (x, X, Y, w, h, rad) => {
                    const r = w / 2;
                    x.beginPath(); x.moveTo(X, Y + r); x.arc(X + r, Y + r, r, Math.PI, 0);
                    x.lineTo(X + w, Y + h - rad); x.arcTo(X + w, Y + h, X + w - rad, Y + h, rad);
                    x.lineTo(X + rad, Y + h); x.arcTo(X, Y + h, X, Y + h - rad, rad); x.closePath();
                };

                return {
                    cfg, svg, n: 4,
                    // High-resolution PNG (1800 x 2600) for print shops, same design as the cards.
                    async png() {
                        const W = 1800, H = 2600;
                        const fonts = ['380 170px "Fraunces Variable"', 'italic 340 110px "Fraunces Variable"', '700 60px "Manrope Variable"'];
                        const [logo] = await Promise.all([
                            load(cfg.logo || ''),
                            Promise.all(fonts.map((f) => document.fonts.load(f))).catch(() => {}),
                        ]);
                        const cv = document.createElement('canvas'); cv.width = W; cv.height = H;
                        const x = cv.getContext('2d');
                        x.fillStyle = IVORY; x.fillRect(0, 0, W, H);

                        // Fine olive frame inset from the edge.
                        x.lineWidth = 4; x.strokeStyle = 'rgba(93,98,52,.4)'; roundRect(x, 60, 60, W - 120, H - 120, 50); x.stroke();

                        x.textAlign = 'center';
                        x.fillStyle = INK; x.font = '380 170px "Fraunces Variable", Georgia, serif';
                        if ('letterSpacing' in x) x.letterSpacing = '-3px';
                        x.fillText(cfg.name, W / 2, 360);

                        // The arch holding the QR: dome radius 650, the code sits low enough to clear the curve.
                        const A = 1300, ax = (W - A) / 2, ay = 480, Q = 1050, qx = (W - Q) / 2, qy = ay + A / 2 - 300, ah = qy + Q + 125 - ay;
                        x.fillStyle = PANEL; arch(x, ax, ay, A, ah, 50); x.fill();
                        x.lineWidth = 4; x.strokeStyle = 'rgba(93,98,52,.45)'; arch(x, ax, ay, A, ah, 50); x.stroke();

                        x.fillStyle = OLIVE; x.font = '700 48px "Manrope Variable", system-ui, sans-serif';
                        if ('letterSpacing' in x) x.letterSpacing = '14px';
                        x.fillText('OUR MENU', W / 2 + 7, ay + 250);

                        const cell = Math.floor(Q / q), ox = qx + (Q - cell * q) / 2, oy = qy + (Q - cell * q) / 2;
                        x.fillStyle = INK;
                        for (let r = 0; r < count; r++) for (let c = 0; c < count; c++) if (qr.isDark(r, c)) x.fillRect(ox + (c + 2) * cell, oy + (r + 2) * cell, cell, cell);
                        if (logo) {
                            const L = Math.round(cell * count * .22), cx = W / 2, cy = qy + Q / 2;
                            x.fillStyle = PANEL; x.beginPath(); x.arc(cx, cy, L / 2 + 22, 0, Math.PI * 2); x.fill();
                            x.save(); x.beginPath(); x.arc(cx, cy, L / 2, 0, Math.PI * 2); x.clip();
                            x.fillStyle = '#fff'; x.fillRect(cx - L / 2, cy - L / 2, L, L);
                            x.drawImage(logo, cx - L / 2, cy - L / 2, L, L); x.restore();
                        }

                        if ('letterSpacing' in x) x.letterSpacing = '0px';
                        x.fillStyle = INK; x.font = 'italic 340 110px "Fraunces Variable", Georgia, serif';
                        x.fillText('Scan to see our menu', W / 2, ay + ah + 210);
                        x.fillStyle = OLIVE; x.font = '700 60px "Manrope Variable", system-ui, sans-serif';
                        if ('letterSpacing' in x) x.letterSpacing = '12px';
                        x.fillText(cfg.short.toUpperCase(), W / 2 + 6, ay + ah + 340);
                        cv.toBlob((b) => {
                            const a = document.createElement('a');
                            a.href = URL.createObjectURL(b);
                            a.download = 'jaru-menu-qr.png';
                            document.body.appendChild(a); a.click(); a.remove();
                            setTimeout(() => URL.revokeObjectURL(a.href), 5000);
                        }, 'image/png');
                    },
                };
            });
        });
    </script>
</x-filament-panels::page>
