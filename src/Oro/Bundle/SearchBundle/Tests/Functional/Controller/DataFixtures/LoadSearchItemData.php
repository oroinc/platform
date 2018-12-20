<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class LoadSearchItemData extends AbstractFixture implements OrderedFixtureInterface
{
    const COUNT = 9;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        for ($ind = 1; $ind <= self::COUNT; $ind++) {
            //create item
            /** @var $item Item */
            $item = new Item();
            //string value
            $item->stringValue  = 'item' . $ind . '@mail.com';
            $item->integerValue = $ind * 1000;
            //decimal
            $item->decimalValue = $ind / 10.0;
            //float
            $item->floatValue = $ind / 10.0 + 10;
            //boolean
            $item->booleanValue = $ind % 3 === 0;
            //blob
            $item->blobValue = "blob-{$ind}";
            //array
            $item->arrayValue = [$ind];
            //datetime
            $date = new \DateTime('2014-12-01', new \DateTimeZone('UTC'));
            $date->add(new \DateInterval("P{$ind}Y"));
            $item->datetimeValue = $date;
            //guid
            $item->guidValue = UUIDGenerator::v4();
            //object
            $item->objectValue = new \stdClass();
            //phone
            $item->phone = sprintf($ind % 2 ? '0123-456%s00' : '%s00987654', $ind);

            $manager->persist($item);
            $this->addReference(sprintf('item_%d', $ind), $item);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
