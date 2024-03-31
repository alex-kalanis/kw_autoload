#!/usr/bin/env php
<?php

define('MIN_NECESSARY_VERSION', '7.4.0');

if (version_compare(phpversion(), MIN_NECESSARY_VERSION, '<')) {
    echo sprintf('PHPVER  [FAIL] bad version %s , need at least %s %s', phpversion(), MIN_NECESSARY_VERSION, PHP_EOL);
    die(1);
}

use kalanis\kw_autoload\Autoload;
use kalanis\kw_autoload\AutoloadException;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestingBase.php';


/**
 * Class Testing
 * @package kalanis\kw_load
 *
 * Testing of autoloader
 * Someone said it is not possible. Ahem...
 * It needs prepared structure with available classes and autoloader that throws an exception when path is not found.
 */
class Testing extends TestingBase
{
    public function __construct()
    {
        // bootstrap settings
        require_once realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , '..', 'src', 'Autoload.php']));

        Autoload::setBasePath(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , 'structure'])));
        // Maybe looks like magic, but it is not
        // Beware! If the file will be found earlier (in bad path) and checks pass (mainly due bad namespace), it will be used!
        // For testing purposes they are in the reverse order
        Autoload::addPath('%2$s%1$s%6$s'); // path on root
        Autoload::addPath('%2$s%1$s%5$s%1$s%6$s'); // module/
        Autoload::addPath('%2$s%1$s%4$s%1$s%5$s%1$s%6$s'); // project dir/module/
        Autoload::addPath('%2$s%1$s%4$s%1$s%5$s%1$ssrc%1$s%6$s'); // project dir/module/src/
        Autoload::addPath('%2$s%1$s%4$s%1$ssrc%1$s%6$s'); // project_dir/src/
        Autoload::addPath('%2$s%1$s%4$s%1$ssrc%1$s%5$s%1$s%6$s'); // project_dir/src/module/
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$s%6$s'); // vendor/project_dir/
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$s%5$s%1$s%6$s'); // vendor/project_dir/module/
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$ssrc%1$s%6$s'); // vendor/project_dir/src/
        Autoload::addPath('%2$s%1$s%3$s%1$s%4$s%1$ssrc%1$s%5$s%1$s%6$s'); // vendor/project_dir/src/module/
        spl_autoload_register('\kalanis\kw_autoload\Autoload::autoloading');
    }

    /**
     * Everything OK with these classes
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
     * Non-existent call class
     * @throws AloadTestingException
     */
    protected function testFullPathFail(): void
    {
        try {
            new user\project\TestClass1F(); // extend
            throw new AloadTestingException('Pass for non-existent class!');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    /**
     * Non-existent base class
     * @throws AloadTestingException
     */
    protected function testFullPathFailExtend(): void
    {
        try {
            new user\project\TestClass2F(); // extend
            throw new AloadTestingException('Pass for non-existent extend!');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    /**
     * Non-existent interface
     * @throws AloadTestingException
     */
    protected function testFullPathFailInterface(): void
    {
        try {
            new user\project\TestClass3F(); // interface
            throw new AloadTestingException('Pass for non-existent interface!');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    /**
     * Non-existent trait in class
     * @throws AloadTestingException
     * @throws AloadSkipException
     * Currently problems with testing non-existent traits
     */
    protected function testFullPathFailTrait(): void
    {
        if (PHP_VERSION_ID <= 70400) {
            try {
                new user\project\TestClass4F(); // trait
                throw new AloadTestingException('Pass for non-existent trait!');
            } catch (AutoloadException $ex) {
                // OK
            }
        } else {
            throw new AloadSkipException('Skipping Traits.');
        }
    }

    /**
     * Parent directory structure, not "user/"
     */
    protected function testUserPathOk(): void
    {
        new user\TestClass10();
        new user\TestClass11();
    }

    /**
     * Custom directory structure outside the "user/"
     */
    protected function testProjectPathOk(): void
    {
        new project\TestClass1();
        new project\TestClass2();
    }

    /**
     * Non-existent class
     * @throws AloadTestingException
     */
    protected function testProjectPathFail(): void
    {
        try {
            new project\TestPClass1F(); // extend
            throw new AloadTestingException('Pass for non-existent class!');
        } catch (AutoloadException $ex) {
            // OK
        }
    }

    /**
     * No namespace for this class, but exists somewhere on the defined paths
     */
    protected function testIndependentOk(): void
    {
        new TestClass20();
    }

    /**
     * Try functions and constants inside the namespace
     * @todo: with php core define the way to get the necessary info for autoloading
     */
    protected function testFuncConstOk(): void
    {
        new \user\project\TestClass9();
    }

    /**
     * Test for Enum type
     * @throws AloadSkipException
     */
    protected function testEnumOk(): void
    {
        if (PHP_VERSION_ID >= 80100) {
            $var = \user\project\TestEnum1::Clubs;
        } else {
            throw new AloadSkipException('Skipping Enums.');
        }
    }

    /**
     * Load on-the-fly
     * @throws AloadTestingException
     */
    protected function testOnTheFly(): void
    {
        try {
            new fly_project\TestFlyClass1(); // extend
            throw new AloadTestingException('Pass for class in unknown path!');
        } catch (AutoloadException $ex) {
            // OK
        }
        Autoload::addPath('%2$s%1$s%4$s%1$s%5$s%1$s_src%1$s%6$s'); // vendor/project_dir/_src/
        new fly_project\TestFlyClass1();
    }
}


$lib = new Testing();
Autoload::testMode(true);
$code = $lib->runner();
Autoload::testMode(false);
die($code);
