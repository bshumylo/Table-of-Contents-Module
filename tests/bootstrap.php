<?php

/**
 * PHPUnit bootstrap for mod_toc.
 *
 * Defines the _JEXEC guard constant the extension sources expect and wires up
 * an autoloader for both the Joomla libraries (when the tests run inside a
 * Joomla installation, e.g. the Docker test image) and the module's own
 * PSR-4 namespace.
 *
 * @package     Bshumylo.Module
 * @subpackage  mod_toc
 *
 * @copyright   (C) 2026 Bohdan Shumylo
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or \define('_JEXEC', 1);

$joomlaRoot = getenv('JOOMLA_ROOT') ?: '';

$candidates = array_filter([
    $joomlaRoot !== '' ? $joomlaRoot . '/libraries/vendor/autoload.php' : null,
    '/var/www/joomla/libraries/vendor/autoload.php',
    '/var/www/html/libraries/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
]);

foreach ($candidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;

        break;
    }
}

if (!class_exists(\Joomla\Registry\Registry::class)) {
    fwrite(
        STDERR,
        "Joomla's vendor autoloader was not found. Set JOOMLA_ROOT to a Joomla installation.\n"
    );

    exit(1);
}

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'Bshumylo\\Module\\Toc\\Site\\';

        if (strncmp($class, $prefix, \strlen($prefix)) !== 0) {
            return;
        }

        $relative = str_replace('\\', '/', substr($class, \strlen($prefix)));
        $path     = __DIR__ . '/../src/' . $relative . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    }
);
