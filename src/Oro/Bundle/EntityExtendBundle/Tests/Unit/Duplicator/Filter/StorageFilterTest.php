<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\EntityExtendBundle\Duplicator\Filter\StorageFilter;
use Oro\Bundle\EntityExtendBundle\Model\ExtendEntityStorage;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Duplicator\Filter\Stub\ExtendEntityStub;
use Oro\Component\Duplicator\PropertyBag;
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

    public function testCopierReceivesPropertyBagWithOwnerClass(): void
    {
        $originalStorage = new ExtendEntityStorage(
            ['some_field' => 'value', 'serialized_normalized' => ['foo' => 1]],
            \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
        );
        $object = new ExtendEntityStub($originalStorage);

        $received = null;
        $this->filter->apply($object, 'extendEntityStorage', function ($bag) use (&$received) {
            $received = $bag;

            return clone $bag;
        });

        self::assertInstanceOf(PropertyBag::class, $received);
        self::assertSame(ExtendEntityStub::class, $received->getOwnerClass());
        self::assertSame(['some_field' => 'value'], $received->toArray());
    }

    public function testNewStorageBuiltFromCopiedBag(): void
    {
        $relation = new \stdClass();
        $object = new ExtendEntityStub(new ExtendEntityStorage(
            ['some_field' => 'value', 'relation' => $relation],
            \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
        ));

        $this->filter->apply($object, 'extendEntityStorage', function (PropertyBag $bag) {
            $copy = clone $bag;
            $copy->some_field = null;
            $copy->added = 'new';

            return $copy;
        });

        $newStorage = $object->extendEntityStorage;
        self::assertSame(
            ['some_field' => null, 'relation' => $relation, 'added' => 'new'],
            $newStorage->getArrayCopy()
        );
        self::assertFalse($newStorage->offsetExists('ownerClass'));
        self::assertSame(\ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS, $newStorage->getFlags());
        self::assertSame($relation, $newStorage->relation);
    }

    public function testSourceBagRetainedWhileFilterLives(): void
    {
        $object = new ExtendEntityStub(new ExtendEntityStorage(
            ['some_field' => 'value'],
            \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
        ));

        $weakRef = null;
        $this->filter->apply($object, 'extendEntityStorage', function ($bag) use (&$weakRef) {
            $weakRef = \WeakReference::create($bag);

            return clone $bag;
        });

        gc_collect_cycles();
        self::assertNotNull($weakRef->get(), 'the source bag must live as long as the filter');

        $this->filter = new StorageFilter();
        gc_collect_cycles();
        self::assertNull($weakRef->get(), 'the source bag must be released with the filter');
    }
}
