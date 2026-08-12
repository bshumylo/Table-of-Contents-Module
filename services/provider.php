<?php

/**
 * @package     Bshumylo.Module
 * @subpackage  mod_toc
 *
 * @copyright   (C) 2026 Bohdan Shumylo
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module as ModuleServiceProvider;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Bshumylo\\Module\\Toc'));
        $container->registerServiceProvider(new HelperFactory('\\Bshumylo\\Module\\Toc\\Site\\Helper'));
        $container->registerServiceProvider(new ModuleServiceProvider());
    }
};
