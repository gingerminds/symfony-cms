import { Controller } from '@hotwired/stimulus';

/**
 * TipTap editor of a WysiwygType field (as the Laravel `wysiwyg.js`): a toolbar built from the
 * extensions of its preset, the HTML written to the hidden textarea (empty: ''). TipTap is loaded
 * on the first editor only (the project importmap provides `@tiptap/core`, `@tiptap/starter-kit`
 * and `@tiptap/extension-table`).
 *
 *     <div data-controller="gm-cms-wysiwyg" data-gm-cms-wysiwyg-extensions-value='["bold","link"]'>
 *         <div data-gm-cms-wysiwyg-target="toolbar"></div>
 *         <div data-gm-cms-wysiwyg-target="editor"></div>
 *         <textarea data-gm-cms-wysiwyg-target="input" hidden></textarea>
 *     </div>
 */
export default class extends Controller {
    static targets = ['toolbar', 'editor', 'input'];
    static values = { extensions: Array, labels: Object, rows: { type: Number, default: 6 } };

    #editor = null;

    async connect() {
        const tiptap = await loadTiptap();

        // Disconnected (or connected again) while TipTap was loading.
        if (!this.element.isConnected || this.#editor) {
            return;
        }

        this.#editor = new tiptap.Editor({
            element: this.editorTarget,
            extensions: buildExtensions(tiptap, this.extensionsValue),
            content: unwrapTableCellParagraphs(this.inputTarget.value),
            onUpdate: ({ editor }) => {
                this.inputTarget.value = editor.isEmpty ? '' : editor.getHTML();
            },
            onTransaction: ({ editor }) => refreshToolbar(this.toolbarTarget, editor),
        });

        this.#editor.view.dom.style.minHeight = `${this.rowsValue * 24}px`;
        this.#editor.view.dom.style.outline = 'none';
        buildToolbar(this.toolbarTarget, this.#editor, this.extensionsValue, this.labelsValue);
        this.#labels().forEach((label) => label.addEventListener('click', this.focus));
    }

    disconnect() {
        this.#labels().forEach((label) => label.removeEventListener('click', this.focus));
        this.#editor?.destroy();
        this.#editor = null;
        this.toolbarTarget.replaceChildren();
    }

    focus = () => {
        this.#editor?.commands.focus();
    };

    #labels() {
        return this.inputTarget.id ? document.querySelectorAll(`label[for="${CSS.escape(this.inputTarget.id)}"]`) : [];
    }
}

let tiptapPromise = null;

function loadTiptap() {
    tiptapPromise ??= Promise.all([
        import('@tiptap/core'),
        import('@tiptap/starter-kit'),
        import('@tiptap/extension-table'),
    ]).then(([core, starterKit, table]) => ({
        Editor: core.Editor,
        StarterKit: starterKit.StarterKit,
        Table: table.Table,
        TableRow: table.TableRow,
        // Inline cells: `<td>text</td>`, no wrapping <p> (as Laravel).
        TableHeader: table.TableHeader.extend({ content: 'inline*' }),
        TableCell: table.TableCell.extend({ content: 'inline*' }),
    }));

    return tiptapPromise;
}

function buildExtensions(tiptap, names) {
    const extensions = [
        tiptap.StarterKit.configure({
            link: names.includes('link') ? { openOnClick: false } : false,
            underline: names.includes('underline') ? {} : false,
        }),
    ];

    if (names.includes('table')) {
        extensions.push(tiptap.Table.configure({ resizable: true }), tiptap.TableRow, tiptap.TableHeader, tiptap.TableCell);
    }

    return extensions;
}

const never = () => false;
const outsideTable = (editor) => !editor.isActive('table');

function chain(command) {
    return (editor) => command(editor.chain().focus()).run();
}

function toggleLink(editor, labels) {
    if (editor.isActive('link')) {
        editor.chain().focus().unsetLink().run();

        return;
    }

    const url = window.prompt(label(labels, 'link_prompt'));

    if (url) {
        editor.chain().focus().setLink({ href: url }).run();
    }
}

const BUTTONS = {
    bold: { icon: 'bi-type-bold', label: 'bold', action: chain((c) => c.toggleBold()), active: 'bold' },
    italic: { icon: 'bi-type-italic', label: 'italic', action: chain((c) => c.toggleItalic()), active: 'italic' },
    underline: { icon: 'bi-type-underline', label: 'underline', action: chain((c) => c.toggleUnderline()), active: 'underline' },
    strike: { icon: 'bi-type-strikethrough', label: 'strike', action: chain((c) => c.toggleStrike()), active: 'strike' },
    link: { icon: 'bi-link-45deg', label: 'link', action: toggleLink, active: 'link' },
    bulletList: { icon: 'bi-list-ul', label: 'bullet_list', action: chain((c) => c.toggleBulletList()), active: 'bulletList' },
    orderedList: { icon: 'bi-list-ol', label: 'ordered_list', action: chain((c) => c.toggleOrderedList()), active: 'orderedList' },
    heading: { icon: 'bi-type-h2', label: 'heading', action: chain((c) => c.toggleHeading({ level: 2 })), active: 'heading' },
    blockquote: { icon: 'bi-blockquote-left', label: 'blockquote', action: chain((c) => c.toggleBlockquote()), active: 'blockquote' },
    horizontalRule: { icon: 'bi-dash-lg', label: 'horizontal_rule', action: chain((c) => c.setHorizontalRule()) },
    undo: { icon: 'bi-arrow-counterclockwise', label: 'undo', action: chain((c) => c.undo()) },
    redo: { icon: 'bi-arrow-clockwise', label: 'redo', action: chain((c) => c.redo()) },
    insertTable: {
        icon: 'bi-table',
        label: 'insert_table',
        action: chain((c) => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true })),
        isDisabled: (editor) => editor.isActive('table'),
    },
    toggleHeaderRow: { icon: 'bi-toggle-on', label: 'toggle_header_row', action: chain((c) => c.toggleHeaderRow()), isDisabled: outsideTable },
    addRowAfter: { icon: 'bi-arrow-bar-down', label: 'add_row_after', action: chain((c) => c.addRowAfter()), isDisabled: outsideTable },
    deleteRow: { icon: 'bi-dash-square', label: 'delete_row', action: chain((c) => c.deleteRow()), isDisabled: outsideTable },
    addColumnAfter: { icon: 'bi-arrow-bar-right', label: 'add_column_after', action: chain((c) => c.addColumnAfter()), isDisabled: outsideTable },
    deleteColumn: { icon: 'bi-x-square', label: 'delete_column', action: chain((c) => c.deleteColumn()), isDisabled: outsideTable },
    deleteTable: { icon: 'bi-trash', label: 'delete_table', action: chain((c) => c.deleteTable()), isDisabled: outsideTable },
};

// A preset entry giving several buttons.
const GROUPS = {
    history: ['undo', 'redo'],
    table: ['insertTable', 'toggleHeaderRow', 'addRowAfter', 'deleteRow', 'addColumnAfter', 'deleteColumn', 'deleteTable'],
};

// English fallbacks of a missing translation.
const FALLBACK_LABELS = {
    bold: 'Bold', italic: 'Italic', underline: 'Underline', strike: 'Strikethrough', link: 'Link', link_prompt: 'URL:',
    bullet_list: 'Bullet list', ordered_list: 'Numbered list', heading: 'Heading', blockquote: 'Quote',
    horizontal_rule: 'Divider', undo: 'Undo', redo: 'Redo', insert_table: 'Insert table',
    toggle_header_row: 'Toggle header row', add_row_after: 'Add row', delete_row: 'Delete row',
    add_column_after: 'Add column', delete_column: 'Delete column', delete_table: 'Delete table',
};

function label(labels, key) {
    return labels[key] || FALLBACK_LABELS[key] || key;
}

function isActive(button, editor) {
    return undefined !== button.active && editor.isActive(button.active);
}

function isDisabled(button, editor) {
    return (button.isDisabled ?? never)(editor);
}

function buildToolbar(toolbar, editor, names, labels) {
    const buttons = names.flatMap((name) => GROUPS[name] ?? [name]).filter((name) => BUTTONS[name]);

    toolbar.replaceChildren(...buttons.map((name) => {
        const button = BUTTONS[name];
        const element = document.createElement('button');
        const icon = document.createElement('i');

        element.type = 'button';
        element.title = label(labels, button.label);
        element.setAttribute('aria-label', element.title);
        element.dataset.name = name;
        element.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'gm-cms-wysiwyg-btn');
        icon.classList.add('bi', button.icon);
        icon.setAttribute('aria-hidden', 'true');
        element.append(icon);
        element.addEventListener('click', () => button.action(editor, labels));

        return element;
    }));
    refreshToolbar(toolbar, editor);
}

function refreshToolbar(toolbar, editor) {
    toolbar.querySelectorAll('[data-name]').forEach((element) => {
        const button = BUTTONS[element.dataset.name];

        const active = isActive(button, editor);

        element.classList.toggle('active', active);

        if (undefined !== button.active) {
            element.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        element.disabled = isDisabled(button, editor);
    });
}

/**
 * Cells saved with a <p> (block content, before the inline cells): unwrapped before parsing,
 * else ProseMirror splits the table (one table per cell). Several paragraphs are joined by <br>.
 */
function unwrapTableCellParagraphs(html) {
    if (!html.includes('<table')) {
        return html;
    }

    const doc = new DOMParser().parseFromString(html, 'text/html');

    doc.querySelectorAll('td, th').forEach((cell) => {
        cell.querySelectorAll(':scope > p').forEach((paragraph, index) => {
            if (index > 0) {
                cell.insertBefore(doc.createElement('br'), paragraph);
            }

            paragraph.replaceWith(...paragraph.childNodes);
        });
    });

    return doc.body.innerHTML;
}
