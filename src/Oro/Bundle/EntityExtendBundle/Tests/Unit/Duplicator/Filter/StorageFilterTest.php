<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\EntityExtendBundle\Duplicator\Filter\StorageFilter;
use Oro\Bundle\EntityExtendBundle\Model\ExtendEntityStorage;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Duplicator\Filter\Stub\ExtendEntityStub;
use PHPUnit\Framework\TestCase;

class StorageFilterTest extends TestCase
{
    private StorageFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->filter = new StorageFilter();
    }

    public function testApplySkipsNonExtendEntity(): void
    {
        $object = new \stdClass();
        $object->extendEntityStorage = new ExtendEntityStorage(['foo' => 'bar']);

        // Should not throw, but also should not do anything meaningful
        $this->filter->apply($object, 'extendEntityStorage', fn ($v) => clone $v);

        self::assertSame('bar', $object->extendEntityStorage['foo']);
    }

    public function testApplySkipsWhenStorageIsNotArrayObject(): void
    {
        $object = new ExtendEntityStub(null);

        $called = false;
        $this->filter->apply($object, 'extendEntityStorage', function () use (&$called) {
            $called = true;
        });

        self::assertFalse($called);
        self::assertNull($object->extendEntityStorage);
    }

    public function testApplyCopiesStorageAndRemovesSerializedNormalized(): void
    {
        $originalStorage = new ExtendEntityStorage(
            [
                'some_field'          => 'value',
                'serialized_normalized' => ['foo' => new \stdClass()],
            ],
            \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
        );
        $object = new ExtendEntityStub($originalStorage);

        $this->filter->apply($object, 'extendEntityStorage', fn ($v) => clone $v);

        $newStorage = $object->extendEntityStorage;
        self::assertNotSame($originalStorage, $newStorage);
        self::assertInstanceOf(ExtendEntityStorage::class, $newStorage);
        self::assertSame('value', $newStorage['some_field']);
        self::assertFalse($newStorage->offsetExists('serialized_normalized'));
    }

    public function testApplyWorksWithoutSerializedNormalizedKey(): void
    {
        $originalStorage = new ExtendEntityStorage(
            ['some_field' => 'value'],
            \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
        );
        $object = new ExtendEntityStub($originalStorage);

        $this->filter->apply($object, 'extendEntityStorage', fn ($v) => clone $v);

        $newStorage = $object->extendEntityStorage;
        self::assertInstanceOf(ExtendEntityStorage::class, $newStorage);
        self::assertSame('value', $newStorage['some_field']);
    }

    public function testApplyProducesDistinctStorageObject(): void
    {
        $originalStorage = new ExtendEntityStorage(
            ['field' => 'data'],
            \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
        );
        $object = new ExtendEntityStub($originalStorage);

        $this->filter->apply($object, 'extendEntityStorage', fn ($v) => clone $v);

        self::assertNotSame($originalStorage, $object->extendEntityStorage);
    }
}
