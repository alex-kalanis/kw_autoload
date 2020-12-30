#!/usr/bin/env php
<?php

define('MIN_NECESSARY_VERSION', '7.2.0');

if (version_compare(phpversion(), MIN_NECESSARY_VERSION, '<')) {
    echo sprintf('PHPVER  [FAIL] bad version %s , need at least %s %s', phpversion(), MIN_NECESSARY_VERSION, PHP_EOL);
    die(1);
}

use kalanis\kw_autoload\Autoload;
use kalanis\kw_autoload\AutoloadException;

require_once __DIR__ . '/TestingBase.php';

/**
 * Class Testing
 * @package kalanis\kw_load
 *
 * Testing of autoloader
 * Someone said it is not possible. Eh...
 * It need prepared structure with available classes and autoloader that throws exceptions
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

    /**
     * @throws AloadTestingException
     */
    protected function testOnTheFly(): void
    {
        try {
            new fly_project\TestFlyClass1(); // extend
            throw new AloadTestingException('Pass for class in unknown path');
        } catch (AutoloadException $ex) {
            // OK
        }
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$s_src%1$s'); // vendor/project_dir/_src/
        new fly_project\TestFlyClass1();
    }
}


$lib = new Testing();
$lib->runner();
