<?php

/**
 * @package     Bshumylo.Module
 * @subpackage  mod_toc
 *
 * @copyright   (C) 2026 Bohdan Shumylo
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Bshumylo\Module\Toc\Site\Helper;

use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Turns the raw module params into sanitised, ready to output values.
 *
 * Everything that ends up in the rendered page (script options, inline style
 * variables, the custom class and the custom CSS) is validated here, so the
 * template only has to escape for its output context.
 *
 * @since  1.0.0
 */
class TocHelper
{
    /**
     * Heading tags the module is ever allowed to pick up.
     *
     * @var    string[]
     * @since  1.0.0
     */
    public const ALLOWED_LEVELS = ['h2', 'h3', 'h4', 'h5', 'h6'];

    /**
     * Placements the module knows how to render.
     *
     * @var    string[]
     * @since  1.0.0
     */
    public const ALLOWED_POSITIONS = ['top', 'left', 'right'];

    /**
     * A single CSS length: a number plus a known unit, or one of the
     * keywords that make sense for a width / font size.
     *
     * @var    string
     * @since  1.0.0
     */
    private const CSS_LENGTH = '/^(?:0|(?:auto|inherit|initial|unset)|-?\d{1,5}(?:\.\d{1,4})?(?:px|em|rem|ex|ch|%|vw|vh|vmin|vmax|pt|pc|cm|mm|in))$/';

    /**
     * A hexadecimal colour, matching what Joomla's own ColorRule accepts
     * (plus the 4/8 digit variants with alpha).
     *
     * @var    string
     * @since  1.0.0
     */
    private const CSS_COLOR = '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i';

    /**
     * Upper bounds, so a stored param can never blow up the page size.
     *
     * @since  1.0.0
     */
    private const MAX_SELECTOR_LENGTH = 500;
    private const MAX_CLASS_LENGTH    = 255;
    private const MAX_CUSTOM_CSS      = 8192;

    /**
     * The default content selector, kept in sync with the manifest default.
     *
     * @var    string
     * @since  1.0.0
     */
    public const DEFAULT_SELECTOR = ".com-content-article__body, [itemprop='articleBody'], .item-page .article-content, .article-content, .uk-article [property='text']";

    /**
     * Build the config handed to the front-end script. The script does the
     * actual heading extraction from the rendered DOM, so every value here
     * has to survive a JSON round trip unchanged.
     *
     * @param   Registry  $params    The module params.
     * @param   integer   $moduleId  The module instance id.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getConfig(Registry $params, int $moduleId): array
    {
        // Stored as a JSON array (normal case for the checkboxes field) or,
        // defensively, as a comma separated string.
        $rawLevels = $params->get('levels', ['h2', 'h3', 'h4']);

        if (\is_string($rawLevels)) {
            $rawLevels = explode(',', $rawLevels);
        }

        if (!\is_array($rawLevels)) {
            $rawLevels = [];
        }

        $levels = array_values(
            array_intersect(
                array_map(
                    static fn($level): string => strtolower(trim((string) $level)),
                    $rawLevels
                ),
                self::ALLOWED_LEVELS
            )
        );

        if (!$levels) {
            $levels = ['h2', 'h3', 'h4'];
        }

        $position = (string) $params->get('position', 'right');

        if (!\in_array($position, self::ALLOWED_POSITIONS, true)) {
            $position = 'right';
        }

        $selector = trim((string) $params->get('content_selector', self::DEFAULT_SELECTOR));

        if ($selector === '' || \strlen($selector) > self::MAX_SELECTOR_LENGTH) {
            $selector = self::DEFAULT_SELECTOR;
        }

        return [
            'id'           => 'mod-toc-' . $moduleId,
            'selector'     => $selector,
            'levels'       => $levels,
            'position'     => $position,
            'minItems'     => min(100, max(1, (int) $params->get('min_items', 2))),
            'numbered'     => (bool) (int) $params->get('numbered', 0),
            'collapsible'  => (bool) (int) $params->get('collapsible', 1),
            'collapsed'    => (bool) (int) $params->get('collapsed', 0),
            'smoothScroll' => (bool) (int) $params->get('smooth_scroll', 1),
            'scrollOffset' => min(500, max(0, (int) $params->get('scroll_offset', 20))),
        ];
    }

    /**
     * Build the presentation values for the wrapper element: the extra CSS
     * class, the inline custom properties and the sanitised custom CSS.
     *
     * @param   Registry  $params  The module params.
     *
     * @return  array{class: string, style: string, customCss: string}
     *
     * @since   1.0.0
     */
    public function getPresentation(Registry $params): array
    {
        return [
            'class'     => $this->getCustomClass($params),
            'style'     => $this->getStyleVariables($params),
            'customCss' => $this->getCustomCss($params),
        ];
    }

    /**
     * The custom CSS classes for the wrapper, reduced to characters that are
     * legal in a CSS identifier (Joomla's CssIdentifier form rule guards the
     * same thing on save; this is the second line of defence for params that
     * were stored before the rule existed or written straight to the DB).
     *
     * @param   Registry  $params  The module params.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function getCustomClass(Registry $params): string
    {
        $raw = (string) $params->get('custom_class', '');

        if (\strlen($raw) > self::MAX_CLASS_LENGTH) {
            $raw = substr($raw, 0, self::MAX_CLASS_LENGTH);
        }

        $classes = [];

        foreach (preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $class) {
            $class = preg_replace('/[^A-Za-z0-9_\-]/', '', $class);

            // A CSS identifier may not start with a digit, two hyphens or a
            // hyphen followed by a digit.
            if ($class === '' || preg_match('/^[0-9]|^--|^-[0-9]/', $class)) {
                continue;
            }

            $classes[] = $class;
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * The inline custom properties for the wrapper. Only values that are a
     * valid CSS length or hex colour are kept, so nothing arbitrary can be
     * smuggled into the style attribute.
     *
     * @param   Registry  $params  The module params.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function getStyleVariables(Registry $params): string
    {
        $lengths = [
            '--toc-width'     => 'width',
            '--toc-font-size' => 'font_size',
        ];

        $colors = [
            '--toc-bg'     => 'bg_color',
            '--toc-border' => 'border_color',
            '--toc-text'   => 'text_color',
            '--toc-link'   => 'link_color',
        ];

        $declarations = [];

        foreach ($lengths as $property => $param) {
            $value = strtolower(trim((string) $params->get($param, '')));

            if ($value !== '' && preg_match(self::CSS_LENGTH, $value)) {
                $declarations[] = $property . ':' . $value;
            }
        }

        foreach ($colors as $property => $param) {
            $value = trim((string) $params->get($param, ''));

            if ($value !== '' && preg_match(self::CSS_COLOR, $value)) {
                $declarations[] = $property . ':' . $value;
            }
        }

        return implode(';', $declarations);
    }

    /**
     * The free form custom CSS, stripped of everything that could leave the
     * module's own rule block or the surrounding <style> element.
     *
     * The field is stored raw on purpose (CSS is not HTML and would be
     * mangled by the default string filter), so the sanitising happens here,
     * on every render.
     *
     * @param   Registry  $params  The module params.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function getCustomCss(Registry $params): string
    {
        $css = (string) $params->get('custom_css', '');

        if (trim($css) === '') {
            return '';
        }

        if (\strlen($css) > self::MAX_CUSTOM_CSS) {
            $css = substr($css, 0, self::MAX_CUSTOM_CSS);
        }

        // Cannot close the style element, cannot open or close a rule block,
        // cannot comment the closing brace away.
        $css = str_replace(['<', '>', '{', '}', '/*', '*/'], '', $css);

        // At-rules would apply outside the module scope; the legacy script
        // in CSS vectors have no business here either.
        $css = preg_replace('/@[a-z-]+[^;]*;?/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/(?:javascript|vbscript)\s*:/i', '', $css);
        $css = preg_replace('/(?:-moz-binding|behavior)\s*:/i', '', $css);

        // A backslash can hide any of the above from a naive filter; CSS
        // escapes are of no use in a plain declaration list.
        $css = str_replace('\\', '', (string) $css);

        return trim((string) $css);
    }
}
