<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

        $this->getSearchIndexer()->reindex();
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
            $item->booleanValue = rand(0, 1) == true;
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
            $item->phone = sprintf($ind % 2 ? '123-456-%s00' : '%s00987654', $ind);

            $manager->persist($item);
        }

        $manager->flush();
    }

    /**
     * @return \Oro\Bundle\SearchBundle\Engine\OrmIndexer
     */
    protected function getSearchIndexer()
    {
        return $this->container->get('oro_search.search.engine.indexer');
    }
}
