<?php

/**
 * @package     Bshumylo.Module
 * @subpackage  mod_toc
 *
 * @copyright   (C) 2026 Bohdan Shumylo
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Bshumylo\Module\Toc\Tests\Unit;

use Joomla\Registry\Registry;
use Bshumylo\Module\Toc\Site\Helper\TocHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

\defined('_JEXEC') or die;

final class TocHelperTest extends TestCase
{
    private TocHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new TocHelper();
    }

    private function config(array $params, int $id = 1): array
    {
        return $this->helper->getConfig(new Registry($params), $id);
    }

    private function presentation(array $params): array
    {
        return $this->helper->getPresentation(new Registry($params));
    }

    /* ----------------------------------------------------------------
     * Functional requirements
     * ---------------------------------------------------------------- */

    public function testDefaultsAppliedWhenParamsEmpty(): void
    {
        $config = $this->config([], 5);

        $this->assertSame('mod-toc-5', $config['id']);
        $this->assertSame(['h2', 'h3', 'h4'], $config['levels']);
        $this->assertSame(2, $config['minItems']);
        $this->assertSame(TocHelper::DEFAULT_SELECTOR, $config['selector']);
    }

    public function testInvalidLevelsAreFilteredOut(): void
    {
        $config = $this->config(['levels' => ['h2', 'h9', 'script', 'h5']]);

        $this->assertSame(['h2', 'h5'], $config['levels']);
    }

    public function testCommaStringLevelsAreAccepted(): void
    {
        $this->assertSame(['h3', 'h4'], $this->config(['levels' => 'h3,h4'])['levels']);
    }

    public function testLevelsAreCaseInsensitiveAndTrimmed(): void
    {
        $this->assertSame(['h2', 'h3'], $this->config(['levels' => ' H2 , h3 '])['levels']);
    }

    public function testLevelsFallBackToDefaultWhenNothingValidRemains(): void
    {
        $this->assertSame(['h2', 'h3', 'h4'], $this->config(['levels' => ['nope']])['levels']);
    }

    /**
     * Placement is the template position's job since 1.1.0, so a value left
     * over in the saved params must not travel into the script options.
     */
    public function testPositionParamIsNoLongerPartOfTheConfig(): void
    {
        $this->assertArrayNotHasKey('position', $this->config(['position' => 'right']));
    }

    public function testMinItemsClampedToAtLeastOne(): void
    {
        $this->assertSame(1, $this->config(['min_items' => 0])['minItems']);
        $this->assertSame(1, $this->config(['min_items' => -50])['minItems']);
    }

    public function testScrollOffsetIsClampedToTheDocumentedRange(): void
    {
        $this->assertSame(0, $this->config(['scroll_offset' => -10])['scrollOffset']);
        $this->assertSame(500, $this->config(['scroll_offset' => 99999])['scrollOffset']);
    }

    public function testBooleanFlagsAreCoercedFromRegistryStrings(): void
    {
        $config = $this->config(['numbered' => '1', 'collapsible' => '0']);

        $this->assertTrue($config['numbered']);
        $this->assertFalse($config['collapsible']);
    }

    /**
     * Colours, font size and width stopped being module params: the box is
     * styled by the template and, on top of that, by the Custom CSS field.
     * Values left in the saved params of an older module must not produce an
     * inline style any more — the presentation carries no style at all.
     */
    public function testAppearanceParamsNoLongerProduceAnInlineStyle(): void
    {
        $presentation = $this->presentation([
            'width'        => '320px',
            'font_size'    => '0.9rem',
            'bg_color'     => '#f8f9fa',
            'border_color' => '#DEE2E6',
            'text_color'   => '#212529',
            'link_color'   => '#0d6efd',
        ]);

        $this->assertArrayNotHasKey('style', $presentation);
        $this->assertSame(['class', 'customCss'], array_keys($presentation));
    }

    /**
     * The custom properties advertised in the Custom CSS field description
     * have to survive the sanitiser, or the documented way of restyling the
     * module would not work.
     */
    public function testDocumentedCustomPropertiesSurviveSanitising(): void
    {
        $css = '--toc-bg: #f8f9fa;--toc-border: #dee2e6;--toc-text: #212529;'
            . '--toc-link: #0d6efd;--toc-font-size: 0.9rem;';

        $this->assertSame($css, $this->presentation(['custom_css' => $css])['customCss']);
    }

    public function testMultipleCustomClassesAreKept(): void
    {
        $this->assertSame('my-toc extra_1', $this->presentation(['custom_class' => 'my-toc  extra_1'])['class']);
    }

    /* ----------------------------------------------------------------
     * SEC-XSS-01 / SEC-XSS-02 — nothing injectable reaches the output
     * ---------------------------------------------------------------- */

    /**
     * Custom properties are the documented styling hook, so the sanitiser
     * has to hold for hostile values written into one as well.
     */
    #[DataProvider('hostileCustomPropertyProvider')]
    public function testHostileCustomPropertyValuesAreSanitised(string $value, array $mustNotContain): void
    {
        $css = $this->presentation(['custom_css' => '--toc-bg:' . $value])['customCss'];

        foreach ($mustNotContain as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $css);
        }
    }

    public static function hostileCustomPropertyProvider(): array
    {
        return [
            'rule block escape' => ['#fff}body{display:none', ['}', '{']],
            'expression'        => ['expression(alert(1))', ['expression(']],
            'markup'            => ['<script>alert(1)</script>', ['<', '>']],
            'javascript uri'    => ['url(javascript:alert(1))', ['javascript:']],
        ];
    }

    #[DataProvider('hostileClassProvider')]
    public function testHostileCustomClassIsStripped(string $value, string $expected): void
    {
        $this->assertSame($expected, $this->presentation(['custom_class' => $value])['class']);
    }

    public static function hostileClassProvider(): array
    {
        return [
            'attribute break-out' => ['a" onclick="alert(1)', 'a onclickalert1'],
            'markup'              => ['<img src=x onerror=alert(1)>', 'img srcx onerroralert1'],
            'leading digit'       => ['1bad', ''],
            'double hyphen'       => ['--bad', ''],
            'only punctuation'    => ['!!!', ''],
        ];
    }

    #[DataProvider('hostileCssProvider')]
    public function testHostileCustomCssIsSanitised(string $value, array $mustNotContain): void
    {
        $css = $this->presentation(['custom_css' => $value])['customCss'];

        foreach ($mustNotContain as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $css);
        }
    }

    public static function hostileCssProvider(): array
    {
        return [
            'style element break-out' => [
                'color:red}</style><script>alert(1)</script><style>{',
                ['</style', '<script', '}', '{'],
            ],
            'rule block escape' => [
                'color:red} body{display:none',
                ['}', '{'],
            ],
            'at-rule' => [
                '@import url(https://evil.example/x.css);color:red',
                ['@import'],
            ],
            'legacy script vectors' => [
                'width:expression(alert(1));behavior:url(x.htc);-moz-binding:url(x.xml)',
                ['expression(', 'behavior:', '-moz-binding:'],
            ],
            'javascript uri' => [
                'background:url(javascript:alert(1))',
                ['javascript:'],
            ],
            'escaped brace' => [
                'color:red\\7d body\\7b display:none',
                ['\\'],
            ],
            'comment hiding the closing brace' => [
                'color:red;/*',
                ['/*'],
            ],
        ];
    }

    public function testHarmlessCustomCssSurvivesSanitising(): void
    {
        $css = $this->presentation(['custom_css' => "color: #333;\nborder-radius: 12px;"])['customCss'];

        $this->assertSame("color: #333;\nborder-radius: 12px;", $css);
    }

    public function testCustomCssIsLengthCapped(): void
    {
        $css = $this->presentation(['custom_css' => str_repeat('a', 20000)])['customCss'];

        $this->assertLessThanOrEqual(8192, \strlen($css));
    }

    public function testOverlongSelectorFallsBackToTheDefault(): void
    {
        $config = $this->config(['content_selector' => str_repeat('.a', 600)]);

        $this->assertSame(TocHelper::DEFAULT_SELECTOR, $config['selector']);
    }

    public function testEmptySelectorFallsBackToTheDefault(): void
    {
        $this->assertSame(TocHelper::DEFAULT_SELECTOR, $this->config(['content_selector' => '   '])['selector']);
    }

    /**
     * The config is embedded in the page as JSON script options, so it has to
     * survive an encode/decode round trip without any HTML-significant
     * character surviving unescaped.
     */
    public function testConfigIsSafelyJsonEncodable(): void
    {
        $config = $this->config([
            'content_selector' => '</script><script>alert(1)</script>',
            'levels'           => ['h2', '"><script>alert(1)</script>'],
        ]);

        $json = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $this->assertIsString($json);
        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        $this->assertSame(['h2'], $config['levels']);
        $this->assertSame(json_decode((string) $json, true), $config);
    }
}
