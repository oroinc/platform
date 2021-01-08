<?php
declare(strict_types=1);

namespace Oro\Component\PhpUtils\Tests\Unit;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Oro\Component\PhpUtils\ClassGenerator;
use PHPUnit\Framework\TestCase;

class ClassGeneratorTest extends TestCase
{
    public function testConstructEmpty(): void
    {
        $cg = new ClassGenerator();

        static::assertEquals("{\n}", $cg->print());
    }

    public function testConstructWithoutNamespace(): void
    {
        $cg = new ClassGenerator('Test');

        static::assertEquals("class Test\n{\n}\n", $cg->print());
    }

    public function testConstructWithNamespace(): void
    {
        $cg = new ClassGenerator('Space\Test');

        static::assertEquals("namespace Space;\n\nclass Test\n{\n}\n", $cg->print());
    }

    public function testAddUseWithNamespace(): void
    {
        $cg = new ClassGenerator('Space\Test');
        $cg->addUse('Something\Useful');

        static::assertEquals("namespace Space;\n\nuse Something\Useful;\n\nclass Test\n{\n}\n", $cg->print());
    }

    public function testAddUseWithoutNamespaceProducesException(): void
    {
        $this->expectException(\Nette\InvalidStateException::class);
        $this->expectExceptionMessage('Cannot add imports to a non-namespaced class.');

        $cg = new ClassGenerator('Test');
        $cg->addUse('Something\Useful');
    }

    public function testPrintWithNamespace(): void
    {
        $cg = new ClassGenerator('Space\Test');

        $namespace = new PhpNamespace('Space');
        $namespace->addClass('Test');
        $expectedCode = (new PsrPrinter())->printNamespace($namespace);

        static::assertEquals($expectedCode, $cg->print());
    }

    public function testPrintWithoutNamespace(): void
    {
        $cg = new ClassGenerator('Test');

        $classType = new ClassType('Test');
        $expectedCode = (new PsrPrinter())->printClass($classType);

        static::assertEquals($expectedCode, $cg->print());
    }

    public function testCloneWithNamespace(): void
    {
        $cg1 = new ClassGenerator('Space\Test');
        $cg1->addMethod('someMethod')->addBody('return "test";');
        $cg2 = clone $cg1;

        static::assertNotSame($cg1->getNamespace(), $cg2->getNamespace());
        static::assertNotSame($cg1->getNamespace()->getClasses(), $cg2->getNamespace()->getClasses());
        static::assertEquals($cg1->print(), $cg2->print());
    }

    public function testCloneWithoutNamespace(): void
    {
        $cg1 = new ClassGenerator('Test');
        $cg1->addMethod('someMethod')->addBody('return "test";');
        $cg2 = clone $cg1;

        static::assertNull($cg2->getNamespace());
        static::assertEquals($cg1->print(), $cg2->print());
    }
}
