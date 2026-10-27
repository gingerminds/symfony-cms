import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/**
 * Rows of a block repeater field (BlockDataType, as the Laravel `repeater.js`): a new row is the
 * collection prototype with a never reused index (the server renumbers the submitted rows in
 * display order); rows are collapsible and reordered by drag & drop.
 */
export default class extends Controller {
    static targets = ['rows', 'row', 'prototype'];
    static values = { index: Number, prototypeName: { type: String, default: '__name__' } };

    #sortable = null;

    connect() {
        this.#sortable = Sortable.create(this.rowsTarget, {
            animation: 150,
            handle: '.gm-cms-repeater-handle',
            draggable: '.gm-cms-repeater-row',
            ghostClass: 'sortable-ghost',
            onEnd: () => this.#renumber(),
        });
        this.#renumber();
    }

    disconnect() {
        this.#sortable?.destroy();
    }

    add() {
        this.rowsTarget.insertAdjacentHTML('beforeend', this.prototypeTarget.innerHTML.replaceAll(this.prototypeNameValue, String(this.indexValue)));
        this.indexValue++;
        // The row to fill is open.
        this.rowTargets.at(-1)?.classList.remove('is-collapsed');
        this.#renumber();
    }

    remove(event) {
        event.stopPropagation();
        event.currentTarget.closest('.gm-cms-repeater-row').remove();
        this.#renumber();
    }

    toggle(event) {
        if (event.target.closest('button, .gm-cms-repeater-handle')) {
            return;
        }

        event.currentTarget.closest('.gm-cms-repeater-row').classList.toggle('is-collapsed');
    }

    #renumber() {
        this.rowTargets.forEach((row, index) => {
            row.querySelector('[data-role="number"]').textContent = String(index + 1);
        });
    }
}
