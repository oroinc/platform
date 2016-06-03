<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\ItemValue;

class LoadItemsValues extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $values = [
        LoadItems::ITEM1,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->values as $reference) {
            /* @var $item Item */
            $item = $this->getReference($reference);

            $value = new ItemValue();
            $value->setEntity($item);

            $manager->persist($value);
        }

        $manager->flush();
    }
}
