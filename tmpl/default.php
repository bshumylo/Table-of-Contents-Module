<?php

/**
 * @package     Bshumylo.Module
 * @subpackage  mod_toc
 *
 * @copyright   (C) 2026 Bohdan Shumylo
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The layout is executed by AbstractModuleDispatcher inside a static closure,
 * so there is no $this here — only the variables extracted from the data
 * returned by Dispatcher::getLayoutData().
 *
 * @var  \Joomla\CMS\Application\CMSWebApplicationInterface  $app
 * @var  \Joomla\Registry\Registry                        $params
 * @var  array                                            $config        sanitised script options
 * @var  array                                            $presentation  sanitised class / custom CSS
 */

$title = trim((string) $params->get('title', ''));
$title = $title !== '' ? $title : Text::_('MOD_TOC_DEFAULT_TITLE');

$document = $app->getDocument();
$wa       = $document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_toc');
$wa->useStyle('mod_toc.style')
    ->useScript('mod_toc.script');

// The script reads its per instance settings from Joomla.getOptions('mod_toc').
$document->addScriptOptions('mod_toc', [$config['id'] => $config]);

if ($presentation['customCss'] !== '') {
    $wa->addInlineStyle(
        '#' . $config['id'] . '{' . $presentation['customCss'] . '}',
        ['name' => 'mod_toc.custom.' . $config['id']]
    );
}

$classes = 'mod-toc'
    . ($presentation['class'] !== '' ? ' ' . $presentation['class'] : '');
?>
<div
    id="<?php echo htmlspecialchars($config['id'], ENT_QUOTES, 'UTF-8'); ?>"
    class="<?php echo htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'); ?>"
>
    <?php if ($config['collapsible']) : ?>
        <details class="mod-toc__details"<?php echo $config['collapsed'] ? '' : ' open'; ?>>
            <summary class="mod-toc__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></summary>
            <nav class="mod-toc__nav" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"></nav>
        </details>
    <?php else : ?>
        <p class="mod-toc__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></p>
        <nav class="mod-toc__nav" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"></nav>
    <?php endif; ?>
</div>
<noscript><?php echo Text::_('MOD_TOC_NOSCRIPT'); ?></noscript>
