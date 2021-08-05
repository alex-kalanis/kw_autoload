<?php

/**
 * The code here is a bit pythonic - more classes in one file
 * That's the intent - you do not need to call include on each other and catching another autoloader
 */
namespace kalanis\kw_autoload;

use Exception;


/**
 * Class AutoloadException
 * @package kalanis\kw_load
 */
final class AutoloadException extends Exception
{
}


/**
 * Class WantedClassInfo
 * @package kalanis\kw_load
 * What we need to know about class
 */
final class WantedClassInfo
{
    const PHP_CLASS_DELIMITER = '\\';
    const PHP_EXTENSION = '.php';

    protected $vendor = '';
    protected $project = '';
    protected $module = '';
    protected $classPath = '';
    protected $className = '';
    protected $finalPath = '';
    protected $escapeUnderscore = false;

    public function __construct(string $className, bool $escapeUnderscore = false)
    {
        $this->className = $className;
        $this->escapeUnderscore = $escapeUnderscore;
        $this->prepare($this->translateUnderscore($className, $escapeUnderscore));
    }

    protected function translateUnderscore(string $className, bool $escapeUnderscore): string
    {
        return $escapeUnderscore ? strtr($className, ['_' => static::PHP_CLASS_DELIMITER]) : $className ;
    }

    protected function prepare(string $className): void
    {
        $classPath = explode(static::PHP_CLASS_DELIMITER, $className);
        $len = count($classPath);
        if ($len > 3) {
            $this->vendor = reset($classPath);
            $this->project = next($classPath);
            $this->module = next($classPath);
            $this->classPath = $this->findPath(array_slice($classPath, 3));
        } elseif ($len > 2) {
            $this->project = reset($classPath);
            $this->module = next($classPath);
            $this->classPath = $this->findPath(array_slice($classPath, 2));
        } elseif ($len > 1) {
            $this->module = reset($classPath);
            $this->classPath = $this->findPath(array_slice($classPath, 1));
        } else {
            $this->classPath = $this->findPath($classPath);
        }
    }

    protected function findPath(array $slashedClass): string
    {
        return implode(DIRECTORY_SEPARATOR, $slashedClass);
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getPath(): string
    {
        return $this->classPath;
    }

    public function getName(): string
    {
        return $this->className;
    }

    public function setFinalPath(string $finalPath): void
    {
        $this->finalPath = $finalPath;
    }

    public function getFinalPath(): string
    {
        return $this->finalPath;
    }

    public function getEscapeUnderscore(): bool
    {
        return $this->escapeUnderscore;
    }
}


/**
 * Class Autoload
 * @package kalanis\kw_load
 *
 * Autoloading of classes
 * analyze set paths with following function
 * @see sprintf
 * and try to load files
 *
 * params in paths:
 * 1 - directory separator by your OS
 * 2 - path to project, set by setBasePath(), usually __DIR__ in root
 * 3 - submodule vendor
 * 4 - project name
 * 5 - module name
 * 6 - rest of path to the file
 */
final class Autoload
{

    /** @var string */
    protected static $basePath = '';
    /** @var string[] */
    protected static $paths = [];
    /** @var WantedClassInfo[] */
    protected static $classesInfo = [];
    /** @var bool */
    protected static $escapingUnderscore = false;
    /** @var bool */
    protected static $testingMode = false;

    /**
     * Where the heck my project is?
     * @param string $path
     */
    public static function setBasePath(string $path): void
    {
        static::$basePath = $path;
    }

    public static function getBasePath(): string
    {
        return static::$basePath;
    }

    /**
     * Add possible path to file
     * @param string $path
     */
    public static function addPath(string $path): void
    {
        static::$paths[] = $path;
    }

    /**
     * Clear paths to files
     */
    public static function clearPaths(): void
    {
        static::$paths = [];
    }

    /**
     * Set already known classes
     * @param WantedClassInfo[] $classesInfo
     */
    public static function setClassesInfo(array $classesInfo): void
    {
        static::$classesInfo = static::$classesInfo + $classesInfo;
    }

    /**
     * Return known classes
     * @return WantedClassInfo[]
     */
    public static function getClassesInfo(): array
    {
        return static::$classesInfo;
    }

    /**
     * Set escaping underscore to slashes
     * @param bool $escapeUnderscore
     */
    public static function escapeUnderscore(bool $escapeUnderscore = false): void
    {
        static::$escapingUnderscore = $escapeUnderscore;
    }

    /**
     * Enable testing - output and die after class not found
     * @param bool $testMode
     */
    public static function testMode(bool $testMode = false): void
    {
        static::$testingMode = $testMode;
    }

    /**
     * Get what testing mode is set
     * @return bool
     */
    public static function getTestMode(): bool
    {
        return static::$testingMode;
    }

    /**
     * Autoloader by directory
     * @param string $className
     * @throws AutoloadException
     */
    public static function autoloading(string $className): void
    {
        if (static::checkLoad($className)) {
            return;
        }

        if (static::alreadyKnown($className)) {
            return;
        }

        $foundFiles = [];
        $info = new WantedClassInfo($className, static::$escapingUnderscore);

//print_r(['pt info', $info]);
        foreach (static::$paths as $path) {
            $currentPath = realpath(sprintf(
                $path,
                DIRECTORY_SEPARATOR, // %1$s
                static::$basePath, // %2$s
                $info->getVendor(), // %3$s
                $info->getProject(), // %4$s
                $info->getModule(), // %5$s
                $info->getPath() . WantedClassInfo::PHP_EXTENSION // %6$s
            ));
//print_r(['pt lookup', $path, $currentPath]);
            if ($currentPath && is_file($currentPath)) {
                $foundFiles[] = $currentPath;

                require_once $currentPath;

                // time for check file if it contains desired class
                if (static::checkLoad($className)) {
                    $info->setFinalPath($currentPath);
                    static::$classesInfo[$className] = $info;
                    return;
                }
            }
        }
        if (static::$testingMode) {
            throw new AutoloadException(sprintf('Class not found. Class name: %s, available files: %s', $className, implode(',', $foundFiles)));
        }
    }

    protected static function alreadyKnown(string $className): bool
    {
        if (isset(static::$classesInfo[$className])) {
            $info = static::$classesInfo[$className];
            if (is_file($info->getFinalPath())) {
                require_once $info->getFinalPath();

                // time for check file if it contains desired class
                if (static::checkLoad($className)) {
                    return true;
                }
            }
            unset(static::$classesInfo[$className]);
        }
        return false;
    }

    protected static function checkLoad(string $className): bool
    {
        if (class_exists($className)) {
            return true;
        }
        if (interface_exists($className)) {
            return true;
        }
        if (trait_exists($className)) {
            return true;
        }
        return false;
    }
}


class Helper
{
    public static function load(string $rootPath, string $vendorPath, string $projectPath = '')
    {
        Autoload::setBasePath($rootPath);
        // maybe looks like magic, but it is not
        Autoload::addPath('%2$s%1$s' . $vendorPath. '%1$s%3$s%1$s%4$s%1$sphp-src%1$s%5$s%1$s%6$s');
        Autoload::addPath('%2$s%1$s' . $vendorPath. '%1$s%3$s%1$s%4$s%1$ssrc%1$s%5$s%1$s%6$s');
        Autoload::addPath('%2$s%1$s' . $vendorPath. '%1$s%3$s%1$s%4$s%1$s%5$s%1$s%6$s');
        Autoload::addPath('%2$s%1$s' . $vendorPath. '%1$s%4$s%1$sphp-src%1$s%5$s%1$s%6$s');
        Autoload::addPath('%2$s%1$s' . $vendorPath. '%1$s%4$s%1$ssrc%1$s%5$s%1$s%6$s');
        Autoload::addPath('%2$s%1$s' . $vendorPath. '%1$s%4$s%1$s%5$s%1$s%6$s');
        Autoload::addPath('%2$s%1$s' . $vendorPath. '%1$s%5$s%1$s%6$s');
        if (!empty($projectPath)) {
            Autoload::addPath('%2$s%1$s' . $projectPath. '%1$s%5$s%1$s%6$s');
            Autoload::addPath('%2$s%1$s' . $projectPath. '%1$s%6$s');
        }
        Autoload::addPath('%2$s%1$s%5$s%1$s%6$s');
        Autoload::addPath('%2$s%1$s%6$s');
        spl_autoload_register('\kalanis\kw_autoload\Autoload::autoloading');
    }
}