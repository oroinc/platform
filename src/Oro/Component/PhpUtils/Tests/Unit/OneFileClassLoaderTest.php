<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\OneFileClassLoader;

class OneFileClassLoaderTest extends \PHPUnit\Framework\TestCase
{
    private function getClassLoader(): OneFileClassLoader
    {
        return new OneFileClassLoader(
            'PhpUtilsOneFileTestNamespace\\',
            __DIR__
            . DIRECTORY_SEPARATOR
            . 'Fixtures'
            . DIRECTORY_SEPARATOR
            . 'PhpUtilsOneFileTestNamespace'
            . DIRECTORY_SEPARATOR
            . 'classes.php'
        );
    }

    public function testLoadClass()
    {
        $className1 = 'PhpUtilsOneFileTestNamespace\Foo';
        $className2 = 'PhpUtilsOneFileTestNamespace\Baz';
        $notExistentClassName = 'PhpUtilsOneFileTestNamespace\Bar';
        $loader = $this->getClassLoader();

        self::assertTrue($loader->loadClass($className1), 'loader->loadClass() result for Foo class');
        self::assertTrue(class_exists($className1), 'class_exists() after loader->loadClass() for Foo class');
        self::assertTrue(class_exists($className2), 'class_exists() after loader->loadClass() for Baz class');
        self::assertFalse(
            class_exists($notExistentClassName),
            'class_exists() after loader->loadClass() for Bar class'
        );

        self::assertTrue($loader->loadClass($className2), 'loader->loadClass() result for Baz class');
        self::assertTrue(
            class_exists($className1),
            'class_exists() after loader->loadClass() for Foo class after loader->loadClass() result for Baz class'
        );
        self::assertTrue(
            class_exists($className2),
            'class_exists() after loader->loadClass() for Baz class after loader->loadClass() result for Baz class'
        );
        self::assertFalse(
            class_exists($notExistentClassName),
            'class_exists() after loader->loadClass() for Baz class after loader->loadClass() result for Baz class'
        );

        self::assertFalse($loader->loadClass($notExistentClassName), 'loader->loadClass() result for Bar class');
        self::assertTrue(
            class_exists($className1),
            'class_exists() after loader->loadClass() for Foo class after loader->loadClass() result for Bar class'
        );
        self::assertTrue(
            class_exists($className2),
            'class_exists() after loader->loadClass() for Baz class after loader->loadClass() result for Bar class'
        );
        self::assertFalse(
            class_exists($notExistentClassName),
            'class_exists() after loader->loadClass() for Baz class after loader->loadClass() result for Bar class'
        );
    }

    public function testLoadClassFromNotRegisteredNamespace()
    {
        $loader = $this->getClassLoader();
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testLoadClassWhenNamespaceIsNotEqualToDirectory()
    {
        $loader = new OneFileClassLoader(
            'AnotherTestNamespace\\',
            __DIR__
            . DIRECTORY_SEPARATOR
            . 'Fixtures'
            . DIRECTORY_SEPARATOR
            . 'PhpUtilsOneFileTestInvalidNamespace'
            . DIRECTORY_SEPARATOR
            . 'classes.php'
        );
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testLoadClassWhenFileWithClassesDoesNotExist()
    {
        $loader = new OneFileClassLoader(
            'AnotherTestNamespace\\',
            __DIR__
            . DIRECTORY_SEPARATOR
            . 'Fixtures'
            . DIRECTORY_SEPARATOR
            . 'PhpUtilsOneFileTestInvalidNamespace'
            . DIRECTORY_SEPARATOR
            . 'not_existent.php'
        );
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testRegister()
    {
        $loader = $this->getClassLoader();
        $loader->register();
        self::assertTrue(class_exists('PhpUtilsOneFileTestNamespace\Baz'), 'class_exists after loader->register()');
    }
}
