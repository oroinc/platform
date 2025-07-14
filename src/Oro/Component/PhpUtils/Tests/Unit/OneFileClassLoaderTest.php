<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\OneFileClassLoader;
use PHPUnit\Framework\TestCase;
use PhpUtilsOneFileTestNamespace\Baz;
use PhpUtilsOneFileTestNamespace\Foo;

class OneFileClassLoaderTest extends TestCase
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

    public function testLoadClass(): void
    {
        $className1 = Foo::class;
        $className2 = Baz::class;
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

    public function testLoadClassFromNotRegisteredNamespace(): void
    {
        $loader = $this->getClassLoader();
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testLoadClassWhenNamespaceIsNotEqualToDirectory(): void
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

    public function testLoadClassWhenFileWithClassesDoesNotExist(): void
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

    public function testRegister(): void
    {
        $loader = $this->getClassLoader();
        $loader->register();
        self::assertTrue(class_exists(Baz::class), 'class_exists after loader->register()');
    }
}
