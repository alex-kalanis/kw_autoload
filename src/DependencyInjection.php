<?php

namespace kalanis\kw_autoload;


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
    protected static ?DependencyInjection $instance = null;

    /** @var array<string, object> */
    protected array $classes = [];

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
     * Add class directly - also add extends and interfaces
     * @param object $willBeRepresented
     * @throws ReflectionException
     */
    public function addClassWithDeepInstances(object $willBeRepresented): void
    {
        $this->addClassDeepInstances(get_class($willBeRepresented), $willBeRepresented);
    }

    /**
     * @param string $forWhat
     * @param object $willBeRepresented
     * @throws ReflectionException
     */
    protected function addClassDeepInstances(string $forWhat, object $willBeRepresented): void
    {
        $ref = new ReflectionClass($forWhat);
        // object itself
        $this->addRep($forWhat, $willBeRepresented);
        // interfaces
        foreach ($ref->getInterfaces() as $interface) {
            $this->addRep($interface->getName(), $willBeRepresented);
        }
        // parent
        if ($ext = $ref->getParentClass()) {
            $this->addClassDeepInstances($ext->getName(), $willBeRepresented);
        }
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
     * @throws ReflectionException
     * @return object|null
     */
    public function initClass(string $which, array $additionalParams = []): ?object
    {
        try {
            $reflectionClass = new ReflectionClass($which);
            if (!$reflectionClass->isInstantiable()) {
                return null;
            }
        } catch (ReflectionException $ex) {
            return null;
        }

        // init - at first without constructor
        try {
            $construct = $reflectionClass->getMethod('__construct');
        } catch (ReflectionException $ex) {
            // no construct - return immediately
            return $reflectionClass->newInstance();
        }

        // fill found params
        $initParams = [];
        foreach ($construct->getParameters() as $parameter) {

            // by type (class/instance name) from internal storage
            $classType = strval($parameter->getType());
            if ($known = $this->getRep($classType)) {
                $initParams[] = $known;
                continue;
            };

            // by type (class name) new instance
            try {
                if ($reflectionInstance = $this->initClass($classType, $additionalParams)) {
                    $initParams[] = $reflectionInstance;
                    continue;
                }
            } catch (ReflectionException $ex) {
                // nothing here
            }

            // by external data - class type
            if (isset($additionalParams[$classType])) {
                $initParams[] = $additionalParams[$classType];
                continue;
            }

            // by external data - param name
            $paramName = strval($parameter->getName());
            if (isset($additionalParams[$paramName])) {
                $initParams[] = $additionalParams[$paramName];
                continue;
            }

            // default value set
            try {
                $defaultParam = $parameter->getDefaultValue();
                $initParams[] = $defaultParam;
                continue;
            } catch (ReflectionException $ex) {
                // set nothing, will fail
                // next...
            }

            // param not found
            throw new ReflectionException(sprintf('Missing definition for param *%s* in class *%s*', $paramName, $which));
        }

        return $reflectionClass->newInstanceArgs($initParams);
    }

    /**
     * Initialize class and store it for future usage - shallow lookup
     * @param string $which
     * @param array<string, mixed> $additionalParams
     * @throws ReflectionException
     * @return object|null
     */
    public function initStoredClass(string $which, array $additionalParams = []): ?object
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

    /**
     * Initialize class and store it for future usage - deep lookup in new class
     * @param string $which
     * @param array<string, mixed> $additionalParams
     * @throws ReflectionException
     * @return object|null
     */
    public function initDeepStoredClass(string $which, array $additionalParams = []): ?object
    {
        if ($try = $this->initStoredClass($which, $additionalParams)) {
            $this->addClassDeepInstances($which, $try);
            return $try;
        }

        return null;
    }
}
