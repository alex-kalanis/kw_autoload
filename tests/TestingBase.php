<?php

use kalanis\kw_autoload\AutoloadException;


class AloadTestingException extends \Exception
{
    // exception for testing purposes
}


class AloadSkipException extends \Exception
{
    // exception for testing purposes - skip the test
}


class TestingBase
{
    public const TEST_PREFIX = 'test';

    /** @var int return code */
    protected int $return = 0;

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
     * @return int
     */
    public function runner(): int
    {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (0 === strpos($method, static::TEST_PREFIX)) {
                $this->caller($method, $method);
            }
        }
        return $this->return;
    }

    /**
     * Call method and tell results to output
     * @param string $fullName
     * @param string $name
     * @param array $arguments
     */
    protected function caller(string $fullName, string $name, array $arguments = []): void
    {
        try {
            call_user_func_array([$this, $fullName], $arguments);
            echo sprintf('%s  [ OK ] %s', str_pad($name, 35), PHP_EOL);
        } catch (AloadSkipException $ex) {
            echo sprintf('%s  [SKIP] %s %s', str_pad($name, 35), $ex->getMessage(), PHP_EOL);
        } catch (AutoloadException | AloadTestingException $ex) {
            $this->return = 1;
            echo sprintf('%s  [FAIL] %s %s', str_pad($name, 35), $ex->getMessage(), PHP_EOL);
        }
    }
}
