<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\API\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
* Load customers
*
* Execute with "php app/console doctrine:fixtures:load"
*/
class LoadSearchItemData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadItems($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
    }

    /**
     * Load items
     *
     * @param ObjectManager $manager
     */
    public function loadItems($manager)
    {
        for ($ind= 1; $ind < 10; $ind++) {
            //create item
            /** @var $item Item */
            $item = new Item();;
            //string value
            $item->stringValue = 'item' . $ind . '@mail.com';
            $item->integerValue = $ind*1000;
            //decimal
            $item->decimalValue = $ind / 10.0 ;
            //float
            $item->floatValue = $ind / 10.0 + 10;
            //boolean
            $item->booleanValue = rand(0, 1) == true;
            //blob
            $item->blobValue = "blob-{$ind}";
            //array
            $item->arrayValue = array($ind);
            //datetime
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
            $date->add(new \DateInterval("P{$ind}Y"));
            $item->datetimeValue = $date;
            //guid
            $item->guidValue = uniqid();
            //object
            $item->objectValue = new \stdClass();

            $manager->persist($item);
        }

        $manager->flush();
    }
}
