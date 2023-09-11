<?php

namespace kalanis\kw_autoload;

if (!class_exists('\kalanis\kw_autoload\Autoload')) {
    require_once __DIR__ . '/Autoload.php';
    Autoload::setBasePath(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , '..', '..', '..', '..'])));
}


use ReflectionClass;
use ReflectionException;


/**
 * Class DependencyInjection
 * @package kalanis\kw_autoload
 * Load classes via Dependency Injection system
 * Cannot initialize everything - some things need to be initialized first and only then they can be used through this DI.
 * That's due necessity to set param types - and many of them can have primitive types like string or integer.
 */
class DependencyInjection
{
    /** @var DependencyInjection|null */
    protected static $instance = null;

    /** @var array<string, object> */
    protected $classes = [];

    public static function getInstance(): self
    {
        if (empty(static::$instance)) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    protected function __construct()
    {
        // limited
    }

    private function __clone()
    {
        // disabled
    }

    /**
     * Add representation of class
     * @param string $forWhat usually what has been returned from "get_class()" or "$obj::class"
     * @param object $willBeRepresented instance itself
     */
    public function addRep(string $forWhat, object $willBeRepresented): void
    {
        $this->classes[$forWhat] = $willBeRepresented;
    }

    /**
     * Add class directly
     * @param object $willBeRepresented
     */
    public function addClassRep(object $willBeRepresented): void
    {
        $this->classes[get_class($willBeRepresented)] = $willBeRepresented;
    }

    /**
     * Get class by its known representation
     * @param string $what
     * @return object|null
     */
    public function getRep(string $what): ?object
    {
        return $this->hasRep($what) ? $this->classes[$what] : null;
    }

    /**
     * Has class known representation in current run?
     * @param string $what
     * @return bool
     */
    public function hasRep(string $what): bool
    {
        return !empty($this->classes[$what]);
    }

    /**
     * Make an alias for known class - can get class by different name
     * @param string $originalClass
     * @param string $newAlias
     */
    public function aliasAs(string $originalClass, string $newAlias): void
    {
        $class = $this->getRep($originalClass);
        if (!is_null($class)) {
            $this->addRep($newAlias, $class);
        }
    }

    /**
     * Initialize class with usage of known representations
     * Use either param type/instance or name to lookup in known representations
     * @param string $which
     * @param array<string, mixed> $additionalParams
     * @throws AutoloadException
     * @return object|null
     */
    public function initClass(string $which, array $additionalParams): ?object
    {
        try {
            $reflectionClass = new ReflectionClass($which);
        } catch (ReflectionException $ex) {
            return null;
        }
        try {
            $construct = $reflectionClass->getMethod('__construct');
        } catch (ReflectionException $ex) {
            // no construct - return immediately
            return $reflectionClass->newInstance();
        }
        $initParams = [];
        foreach ($construct->getParameters() as $parameter) {
            $classType = strval($parameter->getType());
            if ($known = $this->getRep($classType)) {
                $initParams[] = $known;
                continue;
            };
            if (isset($additionalParams[$classType])) {
                $initParams[] = $additionalParams[$classType];
                continue;
            }
            $paramName = strval($parameter->getName());
            if (isset($additionalParams[$paramName])) {
                $initParams[] = $additionalParams[$paramName];
                continue;
            }

            throw new AutoloadException(sprintf('Missing definition for param *%s* in class *%s*', $paramName, $which));
        }

        return $reflectionClass->newInstanceArgs($initParams);
    }

    /**
     * Initialize class and store it for future usage
     * @param string $which
     * @param array<string, mixed> $additionalParams
     * @throws AutoloadException
     * @return object|null
     */
    public function initStoredClass(string $which, array $additionalParams): ?object
    {
        if ($try = $this->getRep($which)) {
            return $try;
        }

        $try = $this->initClass($which, $additionalParams);
        if ($try) {
            $this->addRep($which, $try);
        }

        return $try;
    }
}
