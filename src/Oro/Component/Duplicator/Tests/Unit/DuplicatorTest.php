<?php

namespace Oro\Component\Duplicator\Tests\Unit;

use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter;
use DeepCopy\Filter\KeepFilter;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyMatcher;
use DeepCopy\Matcher\PropertyNameMatcher;
use DeepCopy\Matcher\PropertyTypeMatcher;
use Doctrine\Common\Collections\Collection;
use Oro\Component\Duplicator\Duplicator;
use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Filter\ReplaceValueFilter;
use Oro\Component\Duplicator\Filter\ShallowCopyFilter;
use Oro\Component\Duplicator\Matcher\MatcherFactory;
use Oro\Component\Duplicator\ObjectType;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity1;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity2;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity3;
use Oro\Component\Duplicator\Tests\Unit\Stub\EntityItem1;
use Oro\Component\Duplicator\Tests\Unit\Stub\EntityItem2;

class DuplicatorTest extends \PHPUnit\Framework\TestCase
{
    public function testDuplicate()
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
        /* @var $entityCopy Entity1 */
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

        /* @var $itemCopy EntityItem1 */
        $itemCopy = $entityCopy->getItems()->first();
        /* @var $item EntityItem1 */
        $item = $entity->getItems()->first();
        $this->assertNotSame($item, $itemCopy);
        $this->assertEquals($item, $itemCopy);
        $this->assertEquals($item->getComment(), $itemCopy->getComment());

        /* @var $item2 EntityItem2 */
        $item2 = $item->getItems()->first();
        /* @var  $item2Copy EntityItem2 */
        $item2Copy = $itemCopy->getItems()->first();

        $this->assertEquals($item2, $item2Copy);
        $this->assertNotSame($item2, $item2Copy);

        $this->assertNotSame($item2->getChildEntity(), $item2Copy->getChildEntity());
        $this->assertEquals($item2->getChildEntity(), $item2Copy->getChildEntity());
        $this->assertEquals($item2->getChildEntity()->getValue(), $item2Copy->getChildEntity()->getValue());
    }

    /**
     * @return Entity1
     */
    protected function getEntity()
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

    /**
     * @return EntityItem1
     */
    protected function getEntityItem()
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

    /**
     * @return Duplicator
     */
    protected function createDuplicator()
    {
        $duplicator = new Duplicator();
        $duplicator->setFilterFactory($this->createFilterFactory());
        $duplicator->setMatcherFactory($this->createMatcherFactory());

        return $duplicator;
    }

    /**
     * @return FilterFactory
     */
    protected function createFilterFactory()
    {
        $factory = new FilterFactory();
        $factory->addObjectType(new ObjectType('setNull', SetNullFilter::class))
            ->addObjectType(new ObjectType('keep', KeepFilter::class))
            ->addObjectType(new ObjectType('collection', DoctrineCollectionFilter::class))
            ->addObjectType(new ObjectType('emptyCollection', DoctrineEmptyCollectionFilter::class))
            ->addObjectType(new ObjectType('replaceValue', ReplaceValueFilter::class))
            ->addObjectType(new ObjectType('shallowCopy', ShallowCopyFilter::class));

        return $factory;
    }

    /**
     * @return MatcherFactory
     */
    protected function createMatcherFactory()
    {
        $factory = new MatcherFactory();
        $factory->addObjectType(new ObjectType('property', PropertyMatcher::class))
            ->addObjectType(new ObjectType('propertyName', PropertyNameMatcher::class))
            ->addObjectType(new ObjectType('propertyType', PropertyTypeMatcher::class));

        return $factory;
    }
}
