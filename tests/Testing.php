#!/usr/bin/php
<?php

use kalanis\kw_autoload\Autoload;
use kalanis\kw_autoload\AutoloadException;


class AloadTestingException extends \Exception
{
    // exception for testing purposes
}


class TestingBase
{
    const CRLF = "\r\n";

    public function __call($name, $arguments)
    {
        $fullName = 'test' . ucfirst($name);
        if (method_exists($this, $fullName)) {
            try {
                $this->{$fullName}(...$arguments);
                echo sprintf('%s  [ OK ] %s', str_pad($name, 30), static::CRLF);
            } catch (AutoloadException | AloadTestingException $ex) {
                echo sprintf('%s  [FAIL] %s %s', str_pad($name, 30), $ex->getMessage(), static::CRLF);
            }
        }
    }
}


/**
 * Class Testing
 * @package kalanis\kw_load
 *
 * Testing of autoloader
 * Someone said it is not possible. Eh...
 * It need prepared structure with available classes and autoloader that throws exceptions
 *
 * @method FullPathOk()
 * @method FullPathFail()
 * @method FullPathFailExtend()
 * @method FullPathFailInterface()
 * @method FullPathFailTrait()
 * @method UserPathOk()
 * @method ProjectPathOk()
 * @method ProjectPathFail()
 * @method IndependentOk()
 * @method OnTheFly()
 */
class Testing extends TestingBase
{
    public function __construct()
    {
        // bootstrap settings
        require_once realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , '..', 'src', 'Autoload.php']));

        Autoload::setBasePath(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , 'structure'])));
        Autoload::testMode(true);
        // Maybe looks like magic, but it is not
        // Beware! If the file will be found earlier (in bad path) and checks pass (mainly due bad namespace), it will be used!
        // For testing purposes they are in the reverse order
        Autoload::addPath('%2$s%1$s'); // path on root
        Autoload::addPath('%2$s%1$s%4$s%1$s'); // project dir only
        Autoload::addPath('%2$s%1$s%4$s%1$ssrc%1$s'); // project_dir/src/
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$s'); // vendor/project_dir/
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$ssrc%1$s'); // vendor/project_dir/src/
        spl_autoload_register('\kalanis\kw_autoload\Autoload::autoloading');
    }

    /**
     * @throws AutoloadException
     */
    protected function testFullPathOk(): void
    {
        new user\project\TestClass1();
        new user\project\sub\TestSubClass1();
        new user\project\sub\TestSubClass2(); // extend
        new user\project\TestClass2(); // extend
        new user\project\TestClass3(); // interface
        new user\project\TestClass4(); // trait
        new user\project\TestClass5(); // load already loaded
        new user\project\TestClass6(); // load already loaded
        new user\project\TestClass8(); // load outside src
    }

    /**
     * @throws AloadTestingException
     */
    protected function testFullPathFail(): void
    {
        try {
            new user\project\TestClass1F(); // extend
            throw new AloadTestingException('Pass for non-existent class');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    /**
     * @throws AloadTestingException
     */
    protected function testFullPathFailExtend(): void
    {
        try {
            new user\project\TestClass2F(); // extend
            throw new AloadTestingException('Pass for non-existent extend');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    /**
     * @throws AloadTestingException
     */
    protected function testFullPathFailInterface(): void
    {
        try {
            new user\project\TestClass3F(); // interface
            throw new AloadTestingException('Pass for non-existent interface');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    /**
     * @throws AloadTestingException
     */
    protected function testFullPathFailTrait(): void
    {
        try {
            new user\project\TestClass4F(); // trait
            throw new AloadTestingException('Pass for non-existent trait');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    protected function testUserPathOk(): void
    {
        new user\TestClass10();
        new user\TestClass11();
    }

    protected function testProjectPathOk(): void
    {
        new project\TestClass1();
        new project\TestClass2();
    }

    /**
     * @throws AloadTestingException
     */
    protected function testProjectPathFail(): void
    {
        try {
            new project\TestPClass1F(); // extend
            throw new AloadTestingException('Pass for non-existent class');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    protected function testIndependentOk(): void
    {
        new TestClass20();
    }

    protected function testOnTheFly(): void
    {
        try {
            new fly_project\TestFlyClass1(); // extend
            throw new AloadTestingException('Pass for non-existent class');
        } catch (AutoloadException $ex) {
            // OK
        }
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$s_src%1$s'); // vendor/project_dir/_src/
        new fly_project\TestFlyClass1();
    }
}


$lib = new Testing();
$lib->FullPathOk();
$lib->FullPathFail();
$lib->FullPathFailExtend();
$lib->FullPathFailInterface();
$lib->FullPathFailTrait();
$lib->UserPathOk();
$lib->ProjectPathOk();
$lib->ProjectPathFail();
$lib->IndependentOk();
$lib->OnTheFly();
