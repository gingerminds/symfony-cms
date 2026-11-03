<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Wysiwyg;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * The `gingerminds_cms` sanitizer of `framework.html_sanitizer` (prepended by the bundle, extended
 * by the project configuration): the HTML the WYSIWYG editor can produce (the extensions of every
 * preset), anything else (scripts, event attributes, `javascript:` links...) dropped. Links: http(s),
 * mailto, tel and relative URLs, `rel` forced (a `target` cannot reach the opener); the content of a
 * wrapper (div, span...) is kept. An editor without text (`<p></p>`) gives null.
 */
class WysiwygSanitizer
{
    public const string NAME = 'gingerminds_cms';

    public const string LINK_REL = 'noopener noreferrer';

    /**
     * The `framework.html_sanitizer.sanitizers.gingerminds_cms` configuration.
     */
    public const array CONFIG = [
        'allow_elements' => [
            'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [], 'code' => [], 'pre' => [],
            'a' => ['href', 'target'], 'ul' => [], 'ol' => ['start', 'type'], 'li' => [],
            'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [], 'blockquote' => [], 'hr' => [],
            'table' => [], 'colgroup' => [], 'col' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
            'th' => ['colspan', 'rowspan', 'colwidth'], 'td' => ['colspan', 'rowspan', 'colwidth'],
        ],
        // Wrappers removed, their content kept (the other elements are dropped with their content).
        'block_elements' => ['div', 'span', 'section', 'article', 'font', 'b', 'i'],
        'force_attributes' => ['a' => ['rel' => self::LINK_REL]],
        'allowed_link_schemes' => ['http', 'https', 'mailto', 'tel'],
        'allow_relative_links' => true,
        'max_input_length' => -1,
    ];

    public function __construct(
        private readonly HtmlSanitizerInterface $sanitizer,
    ) {
    }

    public function sanitize(?string $html): ?string
    {
        return self::nullIfEmpty($this->sanitizer->sanitize((string) $html));
    }

    /**
     * Null for an HTML without text, a rule or a table.
     */
    public static function nullIfEmpty(string $html): ?string
    {
        $text = html_entity_decode(strip_tags($html, '<hr><table>'), \ENT_QUOTES | \ENT_HTML5);

        // With `u`, `\s` covers the Unicode spaces (no-break space included).
        return '' === (string) preg_replace('/\s+/u', '', $text) ? null : $html;
    }
}
