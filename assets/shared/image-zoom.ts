/*
 * This file is part of the Expanded Decks project.
 *
 * (c) Expanded Decks contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Click-to-zoom for rich text images whose original is noticeably larger
 * than the rendered size. Opt out per image with the `.no-zoom` class.
 * Images inside a link (or button) are skipped so their own action wins.
 *
 * @see docs/features.md F17.9 — Zoom on large rich text images
 */

const ZOOM_THRESHOLD = 1.25;

let dialog: HTMLDialogElement | null = null;

function getDialog(): HTMLDialogElement {
    if (dialog) {
        return dialog;
    }
    dialog = document.createElement('dialog');
    dialog.className = 'image-zoom-dialog';
    dialog.append(document.createElement('img'));
    // Any click (image or backdrop) closes; Esc is handled natively by <dialog>.
    dialog.addEventListener('click', () => dialog?.close());
    document.body.append(dialog);

    return dialog;
}

function openZoom(image: HTMLImageElement): void {
    const zoomDialog = getDialog();
    const zoomedImage = zoomDialog.querySelector('img') as HTMLImageElement;
    zoomedImage.src = image.currentSrc || image.src;
    zoomedImage.alt = image.alt;
    zoomDialog.showModal();
}

export function isZoomWorthy(naturalWidth: number, renderedWidth: number): boolean {
    return renderedWidth > 0 && naturalWidth > renderedWidth * ZOOM_THRESHOLD;
}

function setup(image: HTMLImageElement): void {
    if (!isZoomWorthy(image.naturalWidth, image.clientWidth)) {
        return;
    }
    image.classList.add('zoomable');
    image.tabIndex = 0;
    image.setAttribute('role', 'button');
    image.addEventListener('click', () => openZoom(image));
    image.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openZoom(image);
        }
    });
}

export function initImageZoom(): void {
    document.querySelectorAll<HTMLImageElement>('.cms-content img:not(.no-zoom)').forEach((image) => {
        // Linked images and card-hover previews already have their own interaction.
        if (image.closest('a, button, .card-hover')) {
            return;
        }
        if (image.complete) {
            setup(image);
        } else {
            image.addEventListener('load', () => setup(image), { once: true });
        }
    });
}
