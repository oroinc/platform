<?php

namespace Oro\Component\Duplicator\Tests\Unit;

use Doctrine\Common\Collections\Collection;
use Oro\Component\Duplicator\Duplicator;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity1;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity2;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity3;
use Oro\Component\Duplicator\Tests\Unit\Stub\EntityItem1;
use Oro\Component\Duplicator\Tests\Unit\Stub\EntityItem2;

class DuplicatorTest extends DuplicatorTestCase
{
    public function testDuplicate(): void
    {
        $now = new \DateTime();
        $now = $now->modify('+1 day');

        $params = [
            [['collection'], ['propertyType', [Collection::class]]],
            [['setNull'], ['propertyName', ['id']]],
            [['keep'], ['propertyName', ['entity']]],
            [['replaceValue', $now], ['property', [Entity1::class, 'createdAt']]],
            [['replaceValue', false], ['property', [Entity1::class, 'bool']]],
            [['replaceValue', ''], ['property', [Entity1::class, 'string']]],
            [['setNull'], ['property', [EntityItem1::class, 'id']]],
            [['shallowCopy'], ['property', [EntityItem2::class, 'childEntity']]],
        ];
        $entity = $this->getEntity();

        $duplicator = $this->createDuplicator();
        /* @var Entity1 $entityCopy */
        $entityCopy = $duplicator->duplicate($entity, $params);

        $this->assertNotSame($entity, $entityCopy);
        $this->assertNotEquals($now, $entity->getCreatedAt());
        $this->assertSame($entityCopy->getCreatedAt(), $now);
        $this->assertEquals($entityCopy->getId(), null);
        $this->assertEquals($entity->getEmail(), $entityCopy->getEmail());
        $this->assertSame($entity->getEntity(), $entityCopy->getEntity());
        $this->assertSame($entity->getEntity()->getTitle(), $entityCopy->getEntity()->getTitle());
        $this->assertSame(true, $entity->getBool());
        $this->assertSame(false, $entityCopy->getBool());
        $this->assertSame('some string', $entity->getString());
        $this->assertSame('', $entityCopy->getString());

        $this->assertNotSame($entityCopy->getItems(), $entity->getItems());
        $this->assertEquals($entityCopy->getItems(), $entity->getItems());

        /* @var EntityItem1 $itemCopy */
        $itemCopy = $entityCopy->getItems()->first();
        /* @var EntityItem1 $item */
        $item = $entity->getItems()->first();
        $this->assertNotSame($item, $itemCopy);
        $this->assertEquals($item, $itemCopy);
        $this->assertEquals($item->getComment(), $itemCopy->getComment());

        /* @var EntityItem2 $item2 */
        $item2 = $item->getItems()->first();
        /* @var  EntityItem2 $item2Copy */
        $item2Copy = $itemCopy->getItems()->first();

        $this->assertEquals($item2, $item2Copy);
        $this->assertNotSame($item2, $item2Copy);

        $this->assertNotSame($item2->getChildEntity(), $item2Copy->getChildEntity());
        $this->assertEquals($item2->getChildEntity(), $item2Copy->getChildEntity());
        $this->assertEquals($item2->getChildEntity()->getValue(), $item2Copy->getChildEntity()->getValue());
    }

    private function getEntity(): Entity1
    {
        $entity2 = new Entity2();
        $entity2->setTitle('open');
        $item = $this->getEntityItem();

        $entity = new Entity1(1);
        $entity->setEmail('test@test.com');

        $entity->addItem($item);
        $entity->setEntity($entity2);

        return $entity;
    }

    private function getEntityItem(): EntityItem1
    {
        $entity3 = new Entity3();
        $entity3->setValue('Value');

        $entityItem2 = new EntityItem2();
        //$entityItem2->setEntity($entity3);
        $entityItem2->setChildEntity($entity3);

        $entityItem1 = new EntityItem1();
        $entityItem1->setComment('Comment');
        $entityItem1->addItem($entityItem2);

        return $entityItem1;
    }

    private function createDuplicator(): Duplicator
    {
        $duplicator = new Duplicator();
        $duplicator->setFilterFactory($this->createFilterFactory());
        $duplicator->setMatcherFactory($this->createMatcherFactory());

        return $duplicator;
    }
}
