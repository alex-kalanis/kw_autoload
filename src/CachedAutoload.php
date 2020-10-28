<?php

namespace kalanis\kw_autoload;

if (!class_exists('\kalanis\kw_autoload\Autoload')) {
    require_once __DIR__ . '/Autoload.php';
    Autoload::setBasePath(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , '..', '..', '..', '..'])));
}


/**
 * Class ClassFormatter
 * @package kalanis\kw_autoload
 * Format records in cache
 * This one uses raw lines
 */
class ClassFormatter
{

    /**
     * @param WantedClassInfo[] $classesInfo
     * @return string
     */
    public function toFormat(array $classesInfo): string
    {
        $dataLines = [];
        foreach ($classesInfo as $info) {
            $dataLines[] = implode($this->recordSplitter(), [$info->getName(), intval($info->getEscapeUnderscore()), $info->getFinalPath()]);
        }
        return implode($this->lineSplitter(), $dataLines);
    }

    /**
     * @param string $content
     * @return WantedClassInfo[]
     */
    public function fromFormat(string $content): array
    {
        $classesInfo = [];
        foreach (explode($this->lineSplitter(), $content) as $item) {
            list($className, $escapes, $finalPath) = explode($this->recordSplitter(), $item, 3);
            $classInfo = new WantedClassInfo($className, boolval($escapes));
            $classInfo->setFinalPath($finalPath);
            $classesInfo[] = $classInfo;
        }
        return $classesInfo;
    }

    protected function lineSplitter(): string
    {
        return "\r\n";
    }

    protected function recordSplitter(): string
    {
        return ';;';
    }
}


/**
 * Class ClassStorage
 * @package kalanis\kw_autoload
 * Class for manipulation with cached paths
 * Extend, edit constants for own cache
 */
class ClassStorage
{
    /** @var ClassFormatter */
    protected $format = null;
    /** @var string */
    protected $storagePath = '';

    public function __construct(ClassFormatter $format, string $storageFile = '')
    {
        $this->format = $format;
        $this->storagePath = implode(DIRECTORY_SEPARATOR, $this->getPath($storageFile));
    }

    protected function getPath(string $storageFile): array
    {
        return [__DIR__,  '..', 'data', (empty($storageFile) ? 'cache.txt' : $storageFile)];
    }

    /**
     * @param WantedClassInfo[] $classesInfo
     */
    public final function save(array $classesInfo): void
    {
        if (is_writable($this->storagePath) || !file_exists($this->storagePath)) {
            file_put_contents($this->storagePath, $this->format->toFormat($classesInfo));
        }
    }

    /**
     * @return WantedClassInfo[]
     */
    public final function load(): array
    {
        if (is_file($this->storagePath) && is_readable($this->storagePath)) {
            return $this->format->fromFormat(file_get_contents($this->storagePath));
        } else {
            return [];
        }
    }

    public final function remove(): void
    {
        if (is_file($this->storagePath)) {
            unlink($this->storagePath);
        }
    }
}


/**
 * Class CachedAutoload
 * @package kalanis\kw_load
 *
 * Autoloading of classes - save cache of each path and then use normal autoload
 */
final class CachedAutoload
{
    public static function useCache(?ClassStorage $storage = null): void
    {
        $storage = $storage ?: new ClassStorage(new ClassFormatter());
        Autoload::setClassesInfo($storage->load());

        register_shutdown_function(function () use ($storage) {
            $storage->save(Autoload::getClassesInfo());
        });
    }
}
