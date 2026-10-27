import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import Sortable from 'sortablejs';

/**
 * Canvas of a block content (BlockCanvasType, as the Laravel `content-blocks.js`). The state lives
 * in the DOM: each block holds its `{uid, type, data}` JSON (`data-block`), the hidden input is
 * resynchronized after every change. The block form and the preview always come from the server
 * (BlockController): `form` loads the form of a new or edited block, `validate` returns the
 * preview or the form with its errors. The modals (picker, form, removal, copy of the structure
 * of another language) are moved under <body>: out of the page form and of the hidden tabs.
 */
export default class extends Controller {
    static targets = ['list', 'input', 'item', 'itemTemplate', 'slotTemplate', 'emptyTemplate', 'modals'];
    static values = { formUrl: String, validateUrl: String, field: String, language: String, labels: Object };

    #modals = {};
    #sortable = null;
    #editing = null;
    #insertBefore = null;
    #removing = null;

    connect() {
        this.#mountModals();
        this.#sortable = Sortable.create(this.listTarget, {
            animation: 150,
            handle: '.gm-cms-block-handle',
            draggable: '.gm-cms-block',
            ghostClass: 'sortable-ghost',
            onEnd: () => this.#changed(),
        });
        this.#refreshSlots();
    }

    disconnect() {
        this.#sortable?.destroy();
        Object.values(this.#modals).forEach((modal) => {
            Modal.getInstance(modal)?.dispose();
            modal.remove();
        });
        this.#modals = {};
    }

    add(event) {
        this.#editing = null;
        this.#insertBefore = event.currentTarget.closest('.gm-cms-blocks-slot');
        Modal.getOrCreateInstance(this.#modals.picker).show();
    }

    edit(event) {
        const item = event.currentTarget.closest('.gm-cms-block');

        this.#editing = item;
        this.#openForm(readBlock(item).type, readBlock(item));
    }

    remove(event) {
        this.#removing = event.currentTarget.closest('.gm-cms-block');
        Modal.getOrCreateInstance(this.#modals.remove).show();
    }

    toggle(event) {
        event.currentTarget.closest('.gm-cms-block').classList.toggle('is-collapsed');
    }

    copyStructure() {
        const modal = this.#modals.copy;
        const select = modal.querySelector('[data-role="source"]');
        const sources = this.#otherCanvases();

        select.replaceChildren(...sources.map((canvas, index) => new Option(canvas.dataset.gmCmsBlocksLanguageValue || String(index + 1), String(index))));
        select.disabled = 0 === sources.length;
        modal.querySelector('[data-role="none"]').hidden = 0 !== sources.length;
        modal.querySelector('[data-role="confirm"]').disabled = 0 === sources.length;
        // Copying replaces the blocks of this language.
        modal.querySelector('[data-role="warning"]').hidden = 0 === this.itemTargets.length;
        Modal.getOrCreateInstance(modal).show();
    }

    #mountModals() {
        const fragment = this.modalsTarget.content.cloneNode(true);

        fragment.querySelectorAll('[data-gm-cms-blocks-modal]').forEach((modal) => {
            this.#modals[modal.dataset.gmCmsBlocksModal] = modal;
            document.body.append(modal);
        });

        const { picker, form, remove, copy } = this.#modals;
        const body = form.querySelector('[data-role="body"]');

        picker.addEventListener('click', (event) => {
            const key = event.target.closest('[data-block-key]')?.dataset.blockKey;

            if (key) {
                // The form modal opens once the picker is hidden: no overlapping transitions.
                picker.addEventListener('hidden.bs.modal', () => this.#openForm(key, null), { once: true });
                Modal.getInstance(picker).hide();
            }
        });

        // A real submit event: the project listeners run first (e.g. the stateless CSRF token).
        form.querySelector('[data-role="save"]').addEventListener('click', () => body.querySelector('form')?.requestSubmit());
        body.addEventListener('submit', (event) => {
            event.preventDefault();
            this.#submit();
        });
        form.addEventListener('hidden.bs.modal', () => body.replaceChildren());

        remove.querySelector('[data-role="confirm"]').addEventListener('click', () => {
            this.#removing?.remove();
            this.#removing = null;
            this.#changed();
            Modal.getInstance(remove).hide();
        });

        copy.querySelector('[data-role="confirm"]').addEventListener('click', () => {
            const source = this.#otherCanvases()[Number(copy.querySelector('[data-role="source"]').value)];

            if (source) {
                this.#copyFrom(source);
            }

            Modal.getInstance(copy).hide();
        });
    }

    async #openForm(key, block) {
        const modal = this.#modals.form;
        const body = modal.querySelector('[data-role="body"]');
        const data = new FormData();

        modal.querySelector('[data-role="title"]').textContent = this.labelsValue.loading;
        body.replaceChildren(loading(this.labelsValue.loading));
        Modal.getOrCreateInstance(modal).show();

        if (block) {
            data.set('uid', block.uid);
            data.set('data', JSON.stringify(block.data ?? {}));
        }

        try {
            const json = await this.#post(this.formUrlValue, key, data);

            body.innerHTML = json.html;
            modal.querySelector('[data-role="title"]').textContent = json.label;
        } catch (error) {
            console.error(error);
            body.replaceChildren(errorAlert(this.labelsValue.load_error));
        }
    }

    async #submit() {
        const modal = this.#modals.form;
        const body = modal.querySelector('[data-role="body"]');
        const form = body.querySelector('form');

        if (!form) {
            return;
        }

        try {
            const json = await this.#post(this.validateUrlValue, form.dataset.type, new FormData(form), true);

            if (json.html) {
                body.innerHTML = json.html;

                return;
            }

            this.#upsert(json);
            Modal.getInstance(modal).hide();
        } catch (error) {
            console.error(error);
            body.prepend(errorAlert(this.labelsValue.load_error));
        }
    }

    async #post(url, key, data, acceptInvalid = false) {
        const response = await fetch(url.replace('__key__', encodeURIComponent(key)), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: data,
        });

        if (!response.ok && !(acceptInvalid && 422 === response.status)) {
            throw new Error(`Block request failed (${response.status})`);
        }

        return response.json();
    }

    #upsert(payload) {
        const item = this.itemTemplateTarget.content.firstElementChild.cloneNode(true);

        item.dataset.block = JSON.stringify({ uid: payload.uid, type: payload.type, data: payload.data });
        item.querySelector('[data-role="icon"]').className = `bi ${payload.icon} text-primary`;
        item.querySelector('[data-role="label"]').textContent = payload.label;
        item.querySelector('[data-role="preview"]').innerHTML = payload.preview;

        if (this.#editing) {
            this.#editing.replaceWith(item);
        } else if (this.#insertBefore?.isConnected) {
            // In place of the clicked "add" slot.
            this.#insertBefore.before(item);
        } else {
            this.listTarget.append(item);
        }

        this.#editing = null;
        this.#insertBefore = null;
        this.#changed();
    }

    #copyFrom(source) {
        this.itemTargets.forEach((item) => item.remove());

        source.querySelectorAll('.gm-cms-block').forEach((sourceItem) => {
            const item = sourceItem.cloneNode(true);
            const block = readBlock(sourceItem);

            item.dataset.block = JSON.stringify({ ...block, uid: newUid() });
            this.listTarget.append(item);
        });

        this.#changed();
    }

    // The canvases of the same field in the other languages (all the tabs are in the page).
    #otherCanvases() {
        const scope = this.element.closest('form') ?? document;

        return [...scope.querySelectorAll('[data-controller~="gm-cms-blocks"]')]
            .filter((canvas) => canvas !== this.element && canvas.dataset.gmCmsBlocksFieldValue === this.fieldValue);
    }

    #changed() {
        // No block: empty, as rendered (an optional translation without content stays blank).
        this.inputTarget.value = 0 === this.itemTargets.length ? '' : JSON.stringify(this.itemTargets.map(readBlock));
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
        this.#refreshSlots();
    }

    // An "add" button before each block and at the end, or the empty state.
    #refreshSlots() {
        this.listTarget.querySelectorAll(':scope > .gm-cms-blocks-slot').forEach((slot) => slot.remove());

        if (0 === this.itemTargets.length) {
            this.listTarget.append(this.emptyTemplateTarget.content.cloneNode(true));

            return;
        }

        this.itemTargets.forEach((item) => item.before(this.slotTemplateTarget.content.cloneNode(true)));
        this.listTarget.append(this.slotTemplateTarget.content.cloneNode(true));
    }
}

function readBlock(item) {
    try {
        return JSON.parse(item.dataset.block || '{}');
    } catch (error) {
        console.warn('Invalid block data', error);

        return {};
    }
}

function newUid() {
    return window.crypto?.randomUUID?.() ?? `${Date.now().toString(16)}${Math.random().toString(16).slice(2)}`;
}

function loading(message) {
    const element = document.createElement('div');
    const spinner = document.createElement('span');

    element.className = 'text-center text-muted py-4';
    spinner.className = 'spinner-border spinner-border-sm me-2';
    spinner.setAttribute('aria-hidden', 'true');
    element.append(spinner, message);

    return element;
}

function errorAlert(message) {
    const element = document.createElement('div');

    element.className = 'alert alert-danger';
    element.textContent = message;

    return element;
}
