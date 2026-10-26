<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Wysiwyg;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Keeps the HTML the WYSIWYG editor can produce (the extensions of every preset): anything else
 * (scripts, event attributes, `javascript:` links...) is dropped. Links: http(s), mailto, tel and
 * relative URLs; the content of a wrapper (div, span...) is kept. An editor without text (`<p></p>`) gives null.
 */
class WysiwygSanitizer
{
    private const array ELEMENTS = [
        'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [], 'code' => [], 'pre' => [],
        'a' => ['href', 'target', 'rel'], 'ul' => [], 'ol' => ['start', 'type'], 'li' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [], 'blockquote' => [], 'hr' => [],
        'table' => [], 'colgroup' => [], 'col' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'colwidth'], 'td' => ['colspan', 'rowspan', 'colwidth'],
    ];

    // Wrappers removed, their content kept (the other elements are dropped with their content).
    private const array WRAPPERS = ['div', 'span', 'section', 'article', 'font', 'b', 'i'];

    private readonly HtmlSanitizerInterface $sanitizer;

    public function __construct()
    {
        $config = new HtmlSanitizerConfig()
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->withMaxInputLength(-1);

        foreach (self::ELEMENTS as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        foreach (self::WRAPPERS as $element) {
            $config = $config->blockElement($element);
        }

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(?string $html): ?string
    {
        $html = $this->sanitizer->sanitize((string) $html);

        // Without text, a rule or a table, the content is empty.
        $text = html_entity_decode(strip_tags($html, '<hr><table>'), \ENT_QUOTES | \ENT_HTML5);

        return '' === (string) preg_replace('/[\s\x{00A0}]+/u', '', $text) ? null : $html;
    }
}
