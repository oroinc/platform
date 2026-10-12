<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Component\Duplicator\DuplicatorInterface;
use Oro\Component\Duplicator\PropertyBag;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity1;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity2;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity3;
use Oro\Component\Duplicator\Tests\Unit\Stub\EntityItem2;

/**
 * Rules applied to a property bag the way ExtendDuplicatorPass registers them: user rules first, then the default keep.
 */
class DuplicatorPropertyBagTest extends DuplicatorTestCase
{
    private DuplicatorInterface $duplicator;

    #[\Override]
    protected function setUp(): void
    {
        $factory = $this->createDuplicatorFactory();
        $factory->addRule(['keep'], ['propertyBagObject', []]);
        $this->duplicator = $factory->create();
    }

    public function testPropertyRuleAppliedToBagEntry(): void
    {
        $holder = $this->createHolder(
            new PropertyBag(Entity1::class, ['extended_field' => 'value', 'other' => 'kept'])
        );

        /** @var EntityItem2 $copy */
        $copy = $this->duplicator->duplicate($holder, [
            [['setNull'], ['property', [Entity1::class, 'extended_field']]],
            [['setNull'], ['property', [Entity2::class, 'other']]],
        ]);

        self::assertSame(['extended_field' => null, 'other' => 'kept'], $copy->getChildEntity()->toArray());
        self::assertSame(['extended_field' => 'value', 'other' => 'kept'], $holder->getChildEntity()->toArray());
    }

    public function testObjectInBagKeptByReferenceWhenNoRuleMatches(): void
    {
        $relation = new Entity2();
        $items = new ArrayCollection([new Entity2()]);
        $holder = $this->createHolder(new PropertyBag(Entity1::class, ['relation' => $relation, 'items' => $items]));

        /** @var EntityItem2 $copy */
        $copy = $this->duplicator->duplicate($holder);

        self::assertNotSame($holder->getChildEntity(), $copy->getChildEntity());
        self::assertSame($relation, $copy->getChildEntity()->relation);
        self::assertSame($items, $copy->getChildEntity()->items);
    }

    public function testDeclaredPropertyWithoutRuleIsStillDeepCopied(): void
    {
        $entity = new Entity3();
        $entity->setValue(new Entity2());

        /** @var Entity3 $copy */
        $copy = $this->duplicator->duplicate($entity);

        self::assertNotSame($entity->getValue(), $copy->getValue());
    }

    public function testUserCollectionRuleWinsOverDefaultKeep(): void
    {
        $item = new Entity2();
        $holder = $this->createHolder(new PropertyBag(Entity1::class, ['items' => new ArrayCollection([$item])]));

        /** @var EntityItem2 $copy */
        $copy = $this->duplicator->duplicate($holder, [
            [['collection'], ['propertyType', [Collection::class]]],
        ]);

        self::assertNotSame($holder->getChildEntity()->items, $copy->getChildEntity()->items);
        self::assertCount(1, $copy->getChildEntity()->items);
        self::assertNotSame($item, $copy->getChildEntity()->items->first());
    }

    public function testNestedDeepCopiedEntityKeepsItsBagObjects(): void
    {
        $nestedRelation = new Entity2();
        $nested = $this->createHolder(new PropertyBag(Entity2::class, ['relation' => $nestedRelation]));
        $holder = $this->createHolder(new PropertyBag(Entity1::class, ['nested' => $nested]));

        /** @var EntityItem2 $copy */
        $copy = $this->duplicator->duplicate($holder, [
            [['shallowCopy'], ['property', [Entity1::class, 'nested']]],
        ]);

        self::assertNotSame($nested, $copy->getChildEntity()->nested);
        self::assertSame($nestedRelation, $copy->getChildEntity()->nested->getChildEntity()->relation);
    }

    private function createHolder(PropertyBag $bag): EntityItem2
    {
        $holder = new EntityItem2();
        $holder->setChildEntity($bag);

        return $holder;
    }
}
