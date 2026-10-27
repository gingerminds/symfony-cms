import { Controller } from '@hotwired/stimulus';

/**
 * Fills a slug field (prefix, slug) from a source field (name, title), as the Laravel `slug-sync`:
 * one second after typing in the source, unless the slug was edited by hand or, without
 * `overwrite`, holds a value it did not generate itself (the saved slug).
 *
 *     <input data-controller="gm-cms-slug-sync"
 *            data-gm-cms-slug-sync-source-value="#page_translations_1_title"
 *            data-gm-cms-slug-sync-overwrite-value="true">
 */
export default class extends Controller {
    static values = { source: String, overwrite: Boolean, delay: { type: Number, default: 1000 } };

    #source = null;
    #edited = false;
    #generated = null;
    #timer = null;

    connect() {
        this.#source = document.querySelector(this.sourceValue);
        this.onSourceInput = this.onSourceInput.bind(this);
        this.onInput = () => { this.#edited = true; };

        this.#source?.addEventListener('input', this.onSourceInput);
        this.element.addEventListener('input', this.onInput);
    }

    disconnect() {
        clearTimeout(this.#timer);
        this.#source?.removeEventListener('input', this.onSourceInput);
        this.element.removeEventListener('input', this.onInput);
    }

    onSourceInput() {
        clearTimeout(this.#timer);
        this.#timer = setTimeout(() => {
            const value = this.element.value;

            if (this.#edited || (!this.overwriteValue && '' !== value && value !== this.#generated)) {
                return;
            }

            this.#generated = slugify(this.#source.value);
            this.element.value = this.#generated;
            this.element.dispatchEvent(new Event('change', { bubbles: true }));
        }, this.delayValue);
    }
}

// Letters without Unicode decomposition (NFKD would drop them).
const TRANSLITERATIONS = {
    'æ': 'ae', 'Æ': 'AE', 'œ': 'oe', 'Œ': 'OE', 'ß': 'ss', 'ẞ': 'SS', 'ø': 'o', 'Ø': 'O',
    'đ': 'd', 'Đ': 'D', 'ð': 'd', 'Ð': 'D', 'ł': 'l', 'Ł': 'L', 'þ': 'th', 'Þ': 'TH', 'ı': 'i',
};

export function slugify(value) {
    // Each run of other characters (hyphens included) becomes a single hyphen: at most one on each end.
    const slug = value
        .replace(/[æÆœŒßẞøØđĐðÐłŁþÞı]/g, (letter) => TRANSLITERATIONS[letter])
        .normalize('NFKD')
        .replace(/\p{M}/gu, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-');

    return slug.slice(slug.startsWith('-') ? 1 : 0, slug.endsWith('-') ? -1 : undefined);
}
