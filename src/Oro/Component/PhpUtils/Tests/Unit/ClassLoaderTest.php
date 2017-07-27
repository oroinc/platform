<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ClassLoader;

class ClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadClass()
    {
        $className = 'PhpUtilsTestNamespace\Foo';
        $loader = new ClassLoader(
            'PhpUtilsTestNamespace\\',
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures'
        );
        self::assertTrue($loader->loadClass($className), 'loader->loadClass() result');
        self::assertTrue(class_exists($className), 'class_exists() after loader->loadClass()');
    }

    public function testLoadNonexistentClass()
    {
        $className = 'PhpUtilsTestNamespace\Bar';
        $loader = new ClassLoader(
            'PhpUtilsTestNamespace\\',
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures'
        );
        self::assertFalse($loader->loadClass($className));
    }

    public function testLoadClassFromNotRegisteredNamespace()
    {
        $className = 'AnotherTestNamespace\Foo';
        $loader = new ClassLoader(
            'PhpUtilsTestNamespace\\',
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures'
        );
        self::assertFalse($loader->loadClass($className));
    }

    public function testLoadClassWhenNamespaceIsNotEqualToDirectory()
    {
        $className = 'AnotherTestNamespace\Foo';
        $loader = new ClassLoader(
            'AnotherTestNamespace\\',
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures'
        );
        self::assertFalse($loader->loadClass($className));
    }

    public function testRegister()
    {
        $loader = new ClassLoader(
            'PhpUtilsTestNamespace\\',
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures'
        );
        $loader->register();
        self::assertTrue(class_exists('PhpUtilsTestNamespace\Baz'), 'class_exists after loader->register()');
    }
}
