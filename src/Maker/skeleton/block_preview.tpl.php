{#
 # Admin preview of the `<?= $key ?>` block (structural, not the site rendering): `block`, `data`, `uid`.
 #}
<div class="gm-cms-block-preview-<?= str_replace('_', '-', $key) ?>">
    {% if data.title %}
        <h3 class="h5 mb-0">{{ data.title }}</h3>
    {% else %}
        <p class="text-muted fst-italic mb-0">{{ 'blocks.message.empty_preview'|trans({}, 'GingermindsCms') }}</p>
    {% endif %}
</div>
