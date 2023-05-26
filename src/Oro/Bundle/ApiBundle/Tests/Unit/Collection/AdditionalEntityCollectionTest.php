<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\AdditionalEntityCollection;

class AdditionalEntityCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AdditionalEntityCollection */
    private $collection;

    protected function setUp(): void
    {
        $this->collection = new AdditionalEntityCollection();
    }

    public function testAdd(): void
    {
        $entity = new \stdClass();

        $this->collection->add($entity);

        self::assertFalse($this->collection->shouldEntityBeRemoved($entity));
        self::assertSame([$entity], $this->collection->getEntities());
    }

    public function testAddWithToBeRemoved(): void
    {
        $entity = new \stdClass();

        $this->collection->add($entity, true);

        self::assertTrue($this->collection->shouldEntityBeRemoved($entity));
        self::assertSame([$entity], $this->collection->getEntities());
    }

    public function testAddForAlreadyAddedWithToBeRemovedEntity(): void
    {
        $entity = new \stdClass();

        $this->collection->add($entity, true);
        $this->collection->add($entity);

        self::assertFalse($this->collection->shouldEntityBeRemoved($entity));
        self::assertSame([$entity], $this->collection->getEntities());
    }

    public function testAddWithToBeRemovedForAlreadyAddedEntity(): void
    {
        $entity = new \stdClass();

        $this->collection->add($entity);
        $this->collection->add($entity, true);

        self::assertTrue($this->collection->shouldEntityBeRemoved($entity));
        self::assertSame([$entity], $this->collection->getEntities());
    }

    public function testRemove(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->collection->add($entity1);
        $this->collection->add($entity2);

        $this->collection->remove($entity1);

        self::assertSame([$entity2], $this->collection->getEntities());
    }

    public function testIsEmptyAndClear(): void
    {
        self::assertTrue($this->collection->isEmpty());

        $this->collection->add(new \stdClass());
        self::assertFalse($this->collection->isEmpty());

        $this->collection->clear();
        self::assertTrue($this->collection->isEmpty());
    }
}
