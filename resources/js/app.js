import './bootstrap';
import Lenis from 'lenis';

function shouldPreventLenis(node) {
    if (!node || typeof node.closest !== 'function') return false;
    return Boolean(
        node.closest(
            '[data-lenis-prevent], .dd-menu, [role="listbox"], [role="dialog"], input[type="range"], #peta, #map-kos, #peta-jaringan, #peta-detail'
        )
    );
}

function initLenisSmoothScroll() {
    const instances = [];
    const byId = new Map();

    // 1. Root window Lenis (aktif untuk halaman full-scroll seperti Beranda / Landing Page)
    const bodyOverflowY = window.getComputedStyle(document.body).overflowY;
    const htmlOverflowY = window.getComputedStyle(document.documentElement).overflowY;
    const isWindowScrollLocked =
        bodyOverflowY === 'hidden' ||
        htmlOverflowY === 'hidden' ||
        document.body.classList.contains('overflow-hidden');

    let rootLenis = null;
    if (!isWindowScrollLocked) {
        rootLenis = new Lenis({
            lerp: 0.1,
            smoothWheel: true,
            wheelMultiplier: 1,
            touchMultiplier: 1,
            anchors: {
                offset: -88,
                duration: 1.15,
            },
            allowNestedScroll: true,
            stopInertiaOnNavigate: true,
            respectReducedMotion: false,
            prevent: shouldPreventLenis,
        });
        instances.push(rootLenis);
        byId.set('window', rootLenis);

        // Tutup menu mobile otomatis saat klik link navigasi ber-anchor
        document.addEventListener('click', (e) => {
            const link = e.target.closest?.('a[href]');
            if (!link) return;

            const href = link.getAttribute('href') || '';
            // Klik menu "Beranda" saat sudah berada di atas halaman Beranda -> smooth scroll ke atas tanpa reload
            if (
                link.dataset.key === 'beranda' &&
                window.location.pathname === '/' &&
                !window.location.search
            ) {
                e.preventDefault();
                rootLenis.scrollTo(0, { duration: 1.1 });
            }

            if (href.includes('#')) {
                const mobilePanel = document.getElementById('nav-mobile');
                if (mobilePanel && !mobilePanel.classList.contains('hidden')) {
                    mobilePanel.classList.add('hidden');
                    document.getElementById('nav-ikon-buka')?.classList.remove('hidden');
                    document.getElementById('nav-ikon-tutup')?.classList.add('hidden');
                }
            }
        });

        // Jika datang dari halaman lain dengan hash (mis. dari Jelajah Kost klik Tentang / Peta -> /#tentang)
        if (window.location.hash && window.location.hash.length > 1) {
            const target = document.querySelector(window.location.hash);
            if (target) {
                setTimeout(() => {
                    rootLenis.scrollTo(target, { offset: -88, duration: 1.1 });
                }, 80);
            }
        }
    }

    // 2. Scoped Lenis untuk semua container scroll mandiri (Panel Kiri & Kanan Jelajah Kost, Dashboard Admin, Login Panel Kiri)
    document.querySelectorAll('[data-lenis-scroll]').forEach((wrapper) => {
        const content =
            wrapper.querySelector(':scope > [data-lenis-content]') ||
            wrapper.firstElementChild;
        if (!content) return;

        const scopedLenis = new Lenis({
            wrapper,
            content,
            eventsTarget: wrapper,
            lerp: 0.12,
            smoothWheel: true,
            wheelMultiplier: 1,
            touchMultiplier: 1,
            allowNestedScroll: true,
            overscroll: true,
            respectReducedMotion: false,
            prevent: shouldPreventLenis,
        });

        instances.push(scopedLenis);
        if (wrapper.id) {
            byId.set(wrapper.id, scopedLenis);
        }

        // Update ukuran scroll otomatis ketika isi panel berubah dinamis (filter kos, buka detail, paginasi)
        let resizeQueued = false;
        const scheduleResize = () => {
            if (resizeQueued) return;
            resizeQueued = true;
            requestAnimationFrame(() => {
                resizeQueued = false;
                scopedLenis.resize();
            });
        };

        const observer = new MutationObserver(scheduleResize);
        observer.observe(content, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style'],
        });
    });

    // Satu loop requestAnimationFrame terpadu untuk seluruh instance Lenis aktif
    if (instances.length > 0) {
        function raf(time) {
            for (let i = 0; i < instances.length; i++) {
                instances[i].raf(time);
            }
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);
    }

    // Helper global untuk reset posisi scroll & paksa hitung ulang batas scroll
    window.kostinScrollToTop = function (id = 'window', immediate = false) {
        const inst = byId.get(id);
        if (inst) {
            inst.resize();
            inst.scrollTo(0, { immediate, duration: immediate ? 0 : 0.7 });
        }
    };

    window.kostinResizeLenis = function () {
        instances.forEach((inst) => inst.resize());
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLenisSmoothScroll);
} else {
    initLenisSmoothScroll();
}
