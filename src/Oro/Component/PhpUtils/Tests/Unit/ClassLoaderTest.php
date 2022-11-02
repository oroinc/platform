<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ClassLoader;

class ClassLoaderTest extends \PHPUnit\Framework\TestCase
{
    private function getClassLoader(string $namespacePrefix = 'PhpUtilsTestNamespace\\'): ClassLoader
    {
        return new ClassLoader(
            $namespacePrefix,
            __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures'
        );
    }

    public function testLoadClass()
    {
        $className = 'PhpUtilsTestNamespace\Foo';
        $loader = $this->getClassLoader();
        self::assertTrue($loader->loadClass($className), 'loader->loadClass() result');
        self::assertTrue(class_exists($className), 'class_exists() after loader->loadClass()');
    }

    public function testLoadNonexistentClass()
    {
        $loader = $this->getClassLoader();
        self::assertFalse($loader->loadClass('PhpUtilsTestNamespace\Bar'));
    }

    public function testLoadClassFromNotRegisteredNamespace()
    {
        $loader = $this->getClassLoader();
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testLoadClassWhenNamespaceIsNotEqualToDirectory()
    {
        $loader = $this->getClassLoader('AnotherTestNamespace\\');
        self::assertFalse($loader->loadClass('AnotherTestNamespace\Foo'));
    }

    public function testRegister()
    {
        $loader = $this->getClassLoader();
        $loader->register();
        self::assertTrue(class_exists('PhpUtilsTestNamespace\Baz'), 'class_exists after loader->register()');
    }
}
