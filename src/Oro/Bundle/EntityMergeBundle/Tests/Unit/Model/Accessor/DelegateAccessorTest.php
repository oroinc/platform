<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DelegateAccessor;
use PHPUnit\Framework\MockObject\MockObject;

class DelegateAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DelegateAccessor $merger */
    protected $accessor;

    /** @var FieldMetadata|MockObject */
    private $metadata;

    private $entity;

    protected function setUp(): void
    {
        $this->metadata = $this->getMockBuilder(FieldMetadata::class)->disableOriginalConstructor()->getMock();
        $this->entity = new class() {
            public $id = 1;
        };
        $this->accessor = new class() extends DelegateAccessor {
            public function xgetElements(): array
            {
                return $this->elements;
            }
        };
    }

    public function testConstructor()
    {
        $foo = $this->createAccessor('foo');
        $bar = $this->createAccessor('bar');

        $accessor = new class([$foo, $bar]) extends DelegateAccessor {
            public function xgetElements(): array
            {
                return $this->elements;
            }
        };

        static::assertEquals(['foo' => $foo, 'bar' => $bar], $accessor->xgetElements());
    }

    public function testGetName()
    {
        static::assertEquals('delegate', $this->accessor->getName());
    }

    public function testAdd()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));

        static::assertEquals(['foo' => $foo, 'bar' => $bar], $this->accessor->xgetElements());
    }

    public function testAddFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add accessor to itself.');

        $this->accessor->add($this->accessor);
    }

    public function testSupportsTrueLast()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));
        $this->accessor->add($baz = $this->createAccessor('baz'));

        $foo->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(false);

        $bar->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(false);

        $baz->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(true);

        static::assertTrue($this->accessor->supports($this->entity, $this->metadata));
    }

    public function testSupportsTrueFirst()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));

        $foo->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(true);

        $bar->expects(static::never())->method('supports');

        static::assertTrue($this->accessor->supports($this->entity, $this->metadata));
    }

    public function testSupportsFalse()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));

        $foo->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(false);

        $bar->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(false);

        static::assertFalse($this->accessor->supports($this->entity, $this->metadata));
    }

    public function testGetValue()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));

        $expectedResult = 'test';

        $foo->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(true);

        $foo->expects(static::once())
            ->method('getValue')
            ->with($this->entity, $this->metadata)
            ->willReturn($expectedResult);

        static::assertEquals($expectedResult, $this->accessor->getValue($this->entity, $this->metadata));
    }

    public function testGetValueFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find accessor for "test" field.');

        $this->accessor->add($foo = $this->createAccessor('foo'));

        $this->metadata->expects(static::once())->method('getFieldName')->willReturn('test');

        $foo->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(false);

        $this->accessor->getValue($this->entity, $this->metadata);
    }

    public function testSetValue()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));

        $value = 'test';

        $foo->expects(static::once())
            ->method('supports')
            ->with($this->entity, $this->metadata)
            ->willReturn(true);

        $foo->expects(static::once())
            ->method('setValue')
            ->with($this->entity, $this->metadata, $value);

        $this->accessor->setValue($this->entity, $this->metadata, $value);
    }

    protected function createAccessor($name)
    {
        $result = $this->createMock(AccessorInterface::class);
        $result->method('getName')->willReturn($name);

        return $result;
    }
}
