<?php

/**
 * @package     Bshumylo.Module
 * @subpackage  mod_toc
 *
 * @copyright   (C) 2026 Bohdan Shumylo
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Bshumylo\Module\Toc\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Bshumylo\Module\Toc\Site\Helper\TocHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_toc.
 *
 * @since  1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data, or false to skip rendering entirely.
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    protected function getLayoutData()
    {
        $input = $this->getApplication()->getInput();

        // Only render while an article is being viewed — the TOC has nothing
        // to extract from on any other page type. Returning false here stops
        // the dispatch, so the module chrome is not rendered either.
        if ($input->getCmd('option') !== 'com_content' || $input->getCmd('view') !== 'article') {
            return false;
        }

        /** @var ?TocHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('TocHelper');

        if (!$helper instanceof TocHelper) {
            return false;
        }

        $data = parent::getLayoutData();

        $data['config']       = $helper->getConfig($data['params'], (int) $data['module']->id);
        $data['presentation'] = $helper->getPresentation($data['params']);

        return $data;
    }
}
