<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DelegateAccessor;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class DelegateAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(FieldMetadata::class);
    }

    /**
     * @return AccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createAccessor(string $name)
    {
        $accessor = $this->createMock(AccessorInterface::class);
        $accessor->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $accessor;
    }

    public function testGetName()
    {
        $accessor = new DelegateAccessor([]);
        self::assertEquals('delegate', $accessor->getName());
    }

    public function testSupportsTrueLast()
    {
        $entity = new EntityStub(1);

        $foo = $this->createAccessor('foo');
        $foo->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(false);

        $bar = $this->createAccessor('bar');
        $bar->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(false);

        $baz = $this->createAccessor('baz');
        $baz->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(true);

        $accessor = new DelegateAccessor([$foo, $bar, $baz]);
        self::assertTrue($accessor->supports($entity, $this->metadata));
    }

    public function testSupportsTrueFirst()
    {
        $entity = new EntityStub(1);

        $foo = $this->createAccessor('foo');
        $foo->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(true);

        $bar = $this->createAccessor('bar');
        $bar->expects(self::never())
            ->method('supports');

        $accessor = new DelegateAccessor([$foo, $bar]);
        self::assertTrue($accessor->supports($entity, $this->metadata));
    }

    public function testSupportsFalse()
    {
        $entity = new EntityStub(1);

        $foo = $this->createAccessor('foo');
        $foo->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(false);

        $bar = $this->createAccessor('bar');
        $bar->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(false);

        $accessor = new DelegateAccessor([$foo, $bar]);
        self::assertFalse($accessor->supports($entity, $this->metadata));
    }

    public function testGetValue()
    {
        $entity = new EntityStub(1);

        $expectedResult = 'test';

        $foo = $this->createAccessor('foo');
        $foo->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(true);
        $foo->expects(self::once())
            ->method('getValue')
            ->with($entity, $this->metadata)
            ->willReturn($expectedResult);

        $accessor = new DelegateAccessor([$foo]);
        self::assertEquals($expectedResult, $accessor->getValue($entity, $this->metadata));
    }

    public function testGetValueFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find accessor for "test" field.');

        $entity = new EntityStub(1);

        $this->metadata->expects(self::once())
            ->method('getFieldName')
            ->willReturn('test');

        $foo = $this->createAccessor('foo');
        $foo->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(false);

        $accessor = new DelegateAccessor([$foo]);
        $accessor->getValue($entity, $this->metadata);
    }

    public function testSetValue()
    {
        $entity = new EntityStub(1);

        $value = 'test';

        $foo = $this->createAccessor('foo');
        $foo->expects(self::once())
            ->method('supports')
            ->with($entity, $this->metadata)
            ->willReturn(true);
        $foo->expects(self::once())
            ->method('setValue')
            ->with($entity, $this->metadata, $value);

        $accessor = new DelegateAccessor([$foo]);
        $accessor->setValue($entity, $this->metadata, $value);
    }
}
