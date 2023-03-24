<?php
declare(strict_types=1);

namespace Oro\Component\PhpUtils\Tests\Unit;

use Nette\InvalidStateException;
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

        self::assertEquals("{\n}", $cg->print());
    }

    public function testConstructWithoutNamespace(): void
    {
        $cg = new ClassGenerator('Test');

        self::assertEquals("class Test\n{\n}\n", $cg->print());
    }

    public function testConstructWithNamespace(): void
    {
        $cg = new ClassGenerator('Space\Test');

        self::assertEquals("namespace Space;\n\nclass Test\n{\n}\n", $cg->print());
    }

    public function testAddUseWithNamespace(): void
    {
        $cg = new ClassGenerator('Space\Test');
        $cg->addUse('Something\Useful');

        self::assertEquals("namespace Space;\n\nuse Something\Useful;\n\nclass Test\n{\n}\n", $cg->print());
    }

    public function testAddUseWithoutNamespaceProducesException(): void
    {
        $this->expectException(InvalidStateException::class);
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

        self::assertEquals($expectedCode, $cg->print());
    }

    public function testPrintWithoutNamespace(): void
    {
        $cg = new ClassGenerator('Test');

        $classType = new ClassType('Test');
        $expectedCode = (new PsrPrinter())->printClass($classType);

        self::assertEquals($expectedCode, $cg->print());
    }

    public function testShouldBePossibleToPrintWithoutNamespace(): void
    {
        $cg = new ClassGenerator('Space\Test');

        $classType = new ClassType('Test');
        $namespace = new PhpNamespace('Space');
        $namespace->addClass('Test');
        $expectedCode = (new PsrPrinter())->printClass($classType, $namespace);

        self::assertEquals($expectedCode, $cg->print(true));
    }

    public function testCloneWithNamespace(): void
    {
        $cg1 = new ClassGenerator('Space\Test');
        $cg1->addMethod('someMethod')->addBody('return "test";');
        $cg2 = clone $cg1;

        self::assertNotSame($cg1->getNamespace(), $cg2->getNamespace());
        self::assertNotSame($cg1->getNamespace()->getClasses(), $cg2->getNamespace()->getClasses());
        self::assertEquals($cg1->print(), $cg2->print());
    }

    public function testCloneWithoutNamespace(): void
    {
        $cg1 = new ClassGenerator('Test');
        $cg1->addMethod('someMethod')->addBody('return "test";');
        $cg2 = clone $cg1;

        self::assertNull($cg2->getNamespace());
        self::assertEquals($cg1->print(), $cg2->print());
    }
}
