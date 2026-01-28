<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
 * Loads Item entities with different booleanValue.
 */
class LoadItemsWithBooleanValue extends AbstractFixture
{
    public const ITEM_TRUE = 'item-boolean-true';
    public const ITEM_FALSE = 'item-boolean-false';
    public const ITEM_NULL = 'item-boolean-null';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $itemTrue = new Item();
        $itemTrue->stringValue = 'Item with true';
        $itemTrue->booleanValue = true;
        $manager->persist($itemTrue);
        $this->addReference(self::ITEM_TRUE, $itemTrue);

        $itemFalse = new Item();
        $itemFalse->stringValue = 'Item with false';
        $itemFalse->booleanValue = false;
        $manager->persist($itemFalse);
        $this->addReference(self::ITEM_FALSE, $itemFalse);

        $itemNull = new Item();
        $itemNull->stringValue = 'Item with null';
        $itemNull->booleanValue = null;
        $manager->persist($itemNull);
        $this->addReference(self::ITEM_NULL, $itemNull);

        $manager->flush();
    }
}
