import { Controller } from '@hotwired/stimulus';

/**
 * Fills a slug field (prefix, slug) from a source field (name, title), as the Laravel `slug-sync`:
 * one second after typing in the source, unless the slug was edited by hand or, without
 * `overwrite`, is already filled.
 *
 *     <input data-controller="gm-cms-slug-sync"
 *            data-gm-cms-slug-sync-source-value="#page_translations_1_title"
 *            data-gm-cms-slug-sync-overwrite-value="true">
 */
export default class extends Controller {
    static values = { source: String, overwrite: Boolean, delay: { type: Number, default: 1000 } };

    #source = null;
    #edited = false;
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
            if (this.#edited || (!this.overwriteValue && '' !== this.element.value)) {
                return;
            }

            this.element.value = slugify(this.#source.value);
            this.element.dispatchEvent(new Event('change', { bubbles: true }));
        }, this.delayValue);
    }
}

export function slugify(value) {
    return value
        .normalize('NFKD')
        .replace(/\p{M}/gu, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}
