for (const gallery of document.querySelectorAll('[data-service-gallery]')) {
    const controls = gallery.querySelector('[data-gallery-controls]');

    if (!controls) {
        continue;
    }

    const track = gallery.querySelector('[data-gallery-track]');
    const counter = gallery.querySelector('[data-gallery-counter]');
    const total = track.children.length;
    const current = () => Math.min(total - 1, Math.max(0, Math.round(track.scrollLeft / track.clientWidth)));

    const move = (step) => {
        const next = (current() + step + total) % total;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        track.scrollTo({ left: next * track.clientWidth, behavior: reduceMotion ? 'instant' : 'smooth' });
    };

    controls.hidden = false;

    for (const button of controls.querySelectorAll('[data-gallery-step]')) {
        button.addEventListener('click', () => move(Number(button.dataset.galleryStep)));
    }

    track.addEventListener('scroll', () => {
        const label = `${current() + 1} / ${total}`;

        if (counter.textContent !== label) {
            counter.textContent = label;
        }
    }, { passive: true });

    track.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            move(event.key === 'ArrowLeft' ? -1 : 1);
        }
    });
}
