<?php

// bootstrap for kwcms 3 - autoloading example
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
require_once(__DIR__ . implode(DIRECTORY_SEPARATOR, ['', '_vendor', 'kalanis', 'kw_load', 'src', 'Autoload.php']));

/// Use following:

\kalanis\kw_autoload\Autoload::setBasePath(realpath(__DIR__ . DIRECTORY_SEPARATOR));
// maybe looks like magic, but it is not
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%3$s%1$s%4$s%1$ssrc%1$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%3$s%1$s%4$s%1$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%4$s%1$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_vendor%1$s%3$s%1$s');
\kalanis\kw_autoload\Autoload::addPath('%2$s%1$s_app%1$s');
spl_autoload_register('\kalanis\kw_autoload\Autoload::autoloading');

/// OR...:
\kalanis\kw_autoload\Helper::load(realpath(__DIR__ . DIRECTORY_SEPARATOR), '_vendor', '_app');

/// If you decided to use cache...
\kalanis\kw_autoload\CachedAutoload::useCache();

/// now you can continue with bootstrapping your project