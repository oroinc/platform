<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class OrmEngineTest extends WebTestCase
{
    const ENTITY_TITLE = 'test-entity-title';

    protected function setUp()
    {
        $this->initClient();
    }

    public function testSearchIndexRealTime()
    {
        $entityManager         = $this->getContainer()->get('doctrine.orm.entity_manager');
        $searchItemRepository  = $entityManager->getRepository('OroSearchBundle:Item');
        $searchIndexRepository = $entityManager->getRepository('OroSearchBundle:IndexText');

        // ensure that search item doesn't exists
        $searchItem = $searchItemRepository->findOneBy(array('title' => self::ENTITY_TITLE));
        $this->assertEmpty($searchItem);

        // create new item
        $item = $this->createItem($entityManager);

        // ensure appropriate search item has been created
        $searchItem = $searchItemRepository->findOneBy(array('title' => self::ENTITY_TITLE));
        $this->assertNotEmpty($searchItem);
        // ensure appropriate search index has been created
        $searchIndex = $searchIndexRepository->findOneBy(array('item' => $searchItem, 'field' => 'all_text'));
        $this->assertNotEmpty($searchIndex);
        $this->assertContains(self::ENTITY_TITLE, $searchIndex->getValue());

        // update test item
        $newTitle          = self::ENTITY_TITLE . '-new';
        $item->stringValue = $newTitle;
        $entityManager->persist($item);
        $entityManager->flush();

        // ensure appropriate search item has been updated
        $searchItem = $searchItemRepository->findOneBy(array('title' => $newTitle));
        $this->assertNotEmpty($searchItem);
        // ensure appropriate search index has been updated
        $searchIndex = $searchIndexRepository->findOneBy(array('item' => $searchItem, 'field' => 'all_text'));
        $this->assertNotEmpty($searchIndex);
        $this->assertContains($newTitle, $searchIndex->getValue());

        // remove created item
        $entityManager->remove($item);
        $entityManager->flush();

        // ensure appropriate search items has been deleted
        $this->assertNull($searchItem->getId());
        $this->assertNull($searchIndex->getId());
    }

    /**
     * @param  EntityManager $entityManager
     * @return Item
     */
    protected function createItem(EntityManager $entityManager)
    {
        $item = new Item();
        $item->stringValue = self::ENTITY_TITLE;

        $entityManager->persist($item);
        $entityManager->flush();

        return $item;
    }
}
