#!/usr/bin/env php
<?php

define('MIN_NECESSARY_VERSION', '7.2.0');

if (version_compare(phpversion(), MIN_NECESSARY_VERSION, '<')) {
    echo sprintf('PHPVER  [FAIL] bad version %s , need at least %s %s', phpversion(), MIN_NECESSARY_VERSION, PHP_EOL);
    die(1);
}

use kalanis\kw_autoload\Autoload;


require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestingBase.php';


/**
 * Class DiTests
 * @package kalanis\kw_load
 *
 * Testing of Dependency Injection
 */
class DiTests extends TestingBase
{
    public function __construct()
    {
        // bootstrap settings
        require_once realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , '..', 'src', 'Autoload.php']));
        require_once realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , '..', 'src', 'DependencyInjection.php']));

        Autoload::setBasePath(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__ , 'structure'])));
        // Maybe looks like magic, but it is not
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
     * @throws AloadTestingException
     */
    protected function testDiStoreAndLoad(): void
    {
        $class = new user\project\TestClass8();
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->addRep(get_class($class), $class);
        if (!$di->getRep(user\project\TestClass8::class) instanceof user\project\TestClass8) {
            throw new AloadTestingException('Died for existent class!');
        }
    }

    /**
     * Trying to get not-yet-known class
     * @throws AloadTestingException
     */
    protected function testNotStoredYet(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        if (!empty($di->getRep(project\TestClass2::class))) {
            throw new AloadTestingException('Died for existent class!');
        }
    }

    /**
     * Everything OK with these classes
     * @throws AloadTestingException
     */
    protected function testDiAliased(): void
    {
        $class = new user\project\TestClass8();
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->addClassRep($class);
        $di->aliasAs(user\project\TestClass8::class, 'alias for class');
        if (!$di->getRep('alias for class') instanceof user\project\TestClass8) {
            throw new AloadTestingException('Died for set alias!');
        }
    }

    /**
     * Trying to get not-yet-known class
     * @throws AloadTestingException
     */
    protected function testNotAliasedYet(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->aliasAs(project\TestClass2::class, 'try to get this');
        if (!empty($di->getRep('try to get this'))) {
            throw new AloadTestingException('Died for aliased class!');
        }
    }

    /**
     * Try to get class when you already know params
     * @throws AloadTestingException
     */
    protected function testWithInnerParams(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->addClassRep(new project\TestClass2());
        $di->addClassRep(new user\project\TestClass8());
        try {
            if (empty($di->initClass(XTest1::class))) {
                throw new AloadTestingException('Died for prepared class!');
            }
        } catch (ReflectionException $ex) {
            // OK
        }
    }

    /**
     * Try to get class when you already pass params
     * @throws AloadTestingException
     */
    protected function testWithOuterParams(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->addRep(project\TestClass2::class, new project\TestClass2());
        try {
            if (empty($di->initClass(XTest2::class, [XTest4::class => new XTest4(), ]))) {
                throw new AloadTestingException('Died for prepared class!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class params!');
        }
    }

    /**
     * Try to get class when you already pass params
     * @throws AloadTestingException
     */
    protected function testWithOuterNamed(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->addRep(project\TestClass2::class, new project\TestClass2());
        try {
            if (empty($di->initClass(XTest3::class, ['testIface' => new XTest4(), ]))) {
                throw new AloadTestingException('Died for prepared class!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class params!');
        }
    }

    /**
     * Try to get class when you already pass params
     * @throws AloadTestingException
     */
    protected function testWithIface(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->addClassRep(new project\TestClass2());
        $di->addRep(IXTst1::class, new XTest4());
        try {
            if (empty($di->initClass(XTest3::class))) {
                throw new AloadTestingException('Died for prepared class!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class params!');
        }
    }

    /**
     * Try to get class which is in fact interface
     * @throws AloadTestingException
     */
    protected function testInitIface(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            if (!empty($di->initClass(IXTst1::class))) {
                throw new AloadTestingException('Init interface!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class params!');
        }
    }

    /**
     * Try to get class when you already pass params
     * @throws AloadTestingException
     */
    protected function testWithoutConstruct(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            if (empty($di->initClass(XTest4::class))) {
                throw new AloadTestingException('Died for prepared class!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class params!');
        }
    }

    /**
     * Try to get class when you did not pass everything
     * @throws AloadTestingException
     */
    protected function testNoParamSet(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            $di->initClass(XTest5::class);
            throw new AloadTestingException('Initialized unprepared class!');
        } catch (ReflectionException $ex) {
            // pass
        }
    }

    /**
     * Try to get class when you pass default params
     * @throws AloadTestingException
     */
    protected function testDefaultParamSet(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            if (empty($di->initClass(XTest6::class))) {
                throw new AloadTestingException('Died for prepared class!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for preset class params!');
        }
    }

    /**
     * Try to get class when you pass default params
     * @throws AloadTestingException
     */
    protected function testAlreadyInstanced(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            $tst = $di->initClass(XTest7::class);
            if (empty($tst)) {
                throw new AloadTestingException('Died for prepared class!');
            }
            /** @var XTest7 $tst */
            if ($tst->cl2 !== $tst->xcl->cl2) {
                throw new AloadTestingException('Different instances!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for preset class params!');
        }
    }

    /**
     * Try to get class when you did not pass everything
     * @throws AloadTestingException
     */
    protected function testStored(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        $di->addRep(project\TestClass2::class, new project\TestClass2());
        $di->addRep(user\project\TestClass8::class, new user\project\TestClass8());
        try {
            $cl1 = $di->initStoredClass(XTest1::class);
            $cl2 = $di->initStoredClass(XTest1::class);
            if ($cl1 !== $cl2) {
                throw new AloadTestingException('Class instances are not the same!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class!');
        }
    }

    /**
     * Try to get class when you set it as instance
     * @throws AloadTestingException
     */
    protected function testExtendsOf(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            $cl1 = new user\project\TestClass2();
            $di->addClassWithDeepInstances($cl1);
            $cl2 = $di->initStoredClass(\user\project\TestClass7::class);
            if ($cl1 !== $cl2) {
                throw new AloadTestingException('Class instances are not the same!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class!');
        }
    }

    /**
     * Try to get class when you set it as instance - deep lookup
     * @throws AloadTestingException
     */
    protected function testDeepExtendsOf(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            $cl1 = $di->initDeepStoredClass(user\project\TestClass2::class);
            $cl2 = $di->getRep(\user\project\TestClass7::class);
            if ($cl1 !== $cl2) {
                throw new AloadTestingException('Class instances are not the same!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class!');
        }
    }

    /**
     * Try to get class when you set it as instance
     * @throws AloadTestingException
     */
    protected function testInterfaceOf(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            $cl1 = new user\project\TestClass3();
            $di->addClassWithDeepInstances($cl1);
            $cl2 = $di->initStoredClass(\user\project\TestIface1::class);
            if ($cl1 !== $cl2) {
                throw new AloadTestingException('Class instances are not the same!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class!');
        }
    }

    /**
     * Try to get class when you set it as instance - deep lookup
     * @throws AloadTestingException
     */
    protected function testDeepInterfaceOf(): void
    {
        $di = \kalanis\kw_autoload\DependencyInjection::getInstance();
        try {
            $cl1 = $di->initDeepStoredClass(user\project\TestClass3::class);
            $cl2 = $di->initStoredClass(\user\project\TestIface1::class);
            if ($cl1 !== $cl2) {
                throw new AloadTestingException('Class instances are not the same!');
            }
        } catch (ReflectionException $ex) {
            throw new AloadTestingException('Died for prepared class!');
        }
    }
}


interface IXTst1 {}


class XTest1
{
    public function __construct(project\TestClass2 $class2, user\project\TestClass8 $class8)
    {
        // just for preset
    }
}


class XTest2
{
    public $cl2 = null;

    public function __construct(project\TestClass2 $class2, XTest4 $class8)
    {
        $this->cl2 = $class2;
    }
}


class XTest3
{
    public function __construct(IXTst1 $testIface)
    {
    }
}


class XTest4 implements IXTst1
{
    public function __construct()
    {
    }
}


class XTest5
{
    public function __construct(int $testData)
    {
    }
}


class XTest6
{
    public function __construct(int $ownData = 27)
    {
    }
}


class XTest7
{
    public $cl2 = null;
    public $xcl = null;

    public function __construct(project\TestClass2 $class2, XTest2 $class, int $ownData = 27)
    {
        $this->cl2 = $class2;
        $this->xcl = $class;
    }
}


$lib = new DiTests();
Autoload::testMode(true);
$code = $lib->runner();
Autoload::testMode(false);
die($code);
