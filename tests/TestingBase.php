<?php

use kalanis\kw_autoload\AutoloadException;


class AloadTestingException extends \Exception
{
    // exception for testing purposes
}


class TestingBase
{
    const TEST_PREFIX = 'test';

    /**
     * Call virtual method - just name without prefix for running only one test
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments)
    {
        $fullName = static::TEST_PREFIX . ucfirst($name);
        if (method_exists($this, $fullName)) {
            $this->caller($fullName, $name, $arguments);
        }
    }

    /**
     * Run this one for get all tests at once
     */
    public function runner(): void
    {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (0 === strpos($method, static::TEST_PREFIX)) {
                $this->caller($method, $method);
            }
        }
    }

    protected function caller(string $fullName, string $name, array $arguments = []): void
    {
        try {
            call_user_func_array([$this, $fullName], $arguments);
            echo sprintf('%s  [ OK ] %s', str_pad($name, 30), PHP_EOL);
        } catch (AutoloadException | AloadTestingException $ex) {
            echo sprintf('%s  [FAIL] %s %s', str_pad($name, 30), $ex->getMessage(), PHP_EOL);
        }
    }
}
