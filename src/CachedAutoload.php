<?php

namespace kalanis\kw_autoload;

if (!class_exists('\kalanis\kw_autoload\Autoload')) {
    require_once __DIR__ . '/Autoload.php';
    Autoload::setBasePath(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , '..', '..', '..', '..'])));
}


final class ClassStorage
{
    const STORAGE_FILE = 'cache.txt';
    const STORAGE_SPLIT_RECORD = ";;";
    const STORAGE_SPLIT_LINE = "\r\n";

    protected $storagePath = '';

    public function __construct()
    {
        $this->storagePath = implode(DIRECTORY_SEPARATOR, [__DIR__,  '..', 'data', static::STORAGE_FILE]);
    }

    /**
     * @param WantedClassInfo[] $classesInfo
     */
    public function save(array $classesInfo): void
    {
        $dataLines = [];
        foreach ($classesInfo as $info) {
            $dataLines[] = implode(static::STORAGE_SPLIT_RECORD, [$info->getName(), intval($info->getEscapeUnderscore()), $info->getFinalPath()]);
        }
        if (is_writable($this->storagePath) || !file_exists($this->storagePath)) {
            file_put_contents($this->storagePath, implode(static::STORAGE_SPLIT_LINE, $dataLines));
        }
    }

    /**
     * @return WantedClassInfo[]
     */
    public function load(): array
    {
        if (is_file($this->storagePath) && is_readable($this->storagePath)) {
            $content = file_get_contents($this->storagePath);
        } else {
            return [];
        }
        $classesInfo = [];
        foreach (explode(static::STORAGE_SPLIT_LINE, $content) as $item) {
            list($className, $escapes, $finalPath) = explode(static::STORAGE_SPLIT_RECORD, $item, 3);
            $classInfo = new WantedClassInfo($className, boolval($escapes));
            $classInfo->setFinalPath($finalPath);
            $classesInfo[] = $classInfo;
        }
        return $classesInfo;
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
    public static function useCache(): void
    {
        $storage = new ClassStorage();
        Autoload::setClassesInfo($storage->load());

        register_shutdown_function(function () use ($storage) {
            $storage->save(Autoload::getClassesInfo());
        });
    }
}
