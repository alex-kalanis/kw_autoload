<?php

// bootstrap for kwcms 3 - autoloading example
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
require_once(__DIR__ . implode(DIRECTORY_SEPARATOR, ['', '_vendor', 'kalanis', 'kw_autoload', 'src', 'Autoload.php']));

/// Use following:

\kalanis\kw_autoload\Autoload::setBasePath(realpath(__DIR__ . DIRECTORY_SEPARATOR));
// maybe looks like magic, but it is not
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%3$s%1$s%4$s%1$sphp-src%1$s%5$s%1$s%6$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%3$s%1$s%4$s%1$ssrc%1$s%5$s%1$s%6$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%3$s%1$s%4$s%1$s%5$s%1$s%6$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%4$s%1$s%5$s%1$s%6$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%3$s%1$s%5$s%1$s%6$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_app%1$s%5$s%1$s%6$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s%5$s%1$s%6$s');
spl_autoload_register('\kalanis\kw_autoload\Autoload::autoloading');

/// OR...:
\kalanis\kw_autoload\Helper::load(realpath(__DIR__ . DIRECTORY_SEPARATOR), '_vendor', '_app');

/// If you decided to use cache...
\kalanis\kw_autoload\CachedAutoload::useCache();

/// now you can continue with bootstrapping your project

// Dependency Injections
$di = \kalanis\kw_autoload\DependencyInjection::getInstance();
// when you want to store unknown class even as instance of its parents and interfaces
$testClass3 = $di->initDeepStoredClass(\user\project\TestClass3::class);
// when you already have all necessities and do not want to store them
$testClass2 = $di->initClass(\project\TestClass2::class, ['testIface' => new XTest4(), ]);

// and when you want to have PSR-DI:
$psr = new \kalanis\kw_autoload\DiPsr();
$testClass4 = $psr->get(\user\project\TestClass4::class);
