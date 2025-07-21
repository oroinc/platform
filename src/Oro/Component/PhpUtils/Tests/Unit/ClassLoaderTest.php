<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ClassLoader;
use PHPUnit\Framework\TestCase;
use PhpUtilsTestNamespace\Baz;
use PhpUtilsTestNamespace\Foo;

class ClassLoaderTest extends TestCase
{
    private function getClassLoader(string $namespacePrefix = 'PhpUtilsTestNamespace\\'): ClassLoader
    {
        return new ClassLoader(
            $namespacePrefix,
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures'
        );
    }

    public function testLoadClass(): void
    {
        $className = Foo::class;
        $loader = $this->getClassLoader();
        self::assertTrue($loader->loadClass($className), 'loader->loadClass() result');
        self::assertTrue(class_exists($className), 'class_exists() after loader->loadClass()');
    }

    public function testLoadNonexistentClass(): void
    {
        $loader = $this->getClassLoader();
        self::assertFalse($loader->loadClass('PhpUtilsTestNamespace\Bar'));
    }

    public function testLoadClassFromNotRegisteredNamespace(): void
    {
        $loader = $this->getClassLoader();
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testLoadClassWhenNamespaceIsNotEqualToDirectory(): void
    {
        $loader = $this->getClassLoader('AnotherTestNamespace\\');
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testRegister(): void
    {
        $loader = $this->getClassLoader();
        $loader->register();
        self::assertTrue(class_exists(Baz::class), 'class_exists after loader->register()');
    }
}
