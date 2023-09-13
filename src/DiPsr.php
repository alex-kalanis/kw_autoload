<?php

namespace kalanis\kw_autoload;


use Psr\Container;
use ReflectionException;


/**
 * Class DiPsr
 * @package kalanis\kw_autoload
 * Load classes via Dependency Injection system
 * This is adapter for PHP PSR-11
 * @link https://www.php-fig.org/psr/psr-11/
 */
class DiPsr implements Container\ContainerInterface
{
    public function get(string $id)
    {
        try {
            $class = DependencyInjection::getInstance()->initDeepStoredClass($id);
            if (empty($class)) {
                throw new NotFoundException(sprintf('The class *%s* has not been set in DI', $id));
            }
            return $class;
        } catch (ReflectionException $ex) {
            throw new ContainerException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    public function has(string $id): bool
    {
        return DependencyInjection::getInstance()->hasRep($id);
    }
}


class ContainerException extends ReflectionException implements Container\ContainerExceptionInterface
{}


class NotFoundException extends ContainerException implements Container\NotFoundExceptionInterface
{}
