<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Engine\Orm;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * @dbIsolation
 * @dbReindex
 */
class OrmEngineTest extends WebTestCase
{
    const ENTITY_TITLE = 'test-entity-title';

    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_search.engine') != 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }

    public function testSearchIndexRealTime()
    {
        $entityManager         = $this->getContainer()->get('doctrine.orm.entity_manager');
        $searchEntityManager   = $this->getContainer()->get('doctrine')->getManagerForClass('OroSearchBundle:Item');
        $searchItemRepository  = $searchEntityManager->getRepository('OroSearchBundle:Item');
        $searchIndexRepository = $searchEntityManager->getRepository('OroSearchBundle:IndexText');

        // ensure that search item doesn't exists
        $searchItem = $searchItemRepository->findOneBy(['title' => self::ENTITY_TITLE]);
        $this->assertEmpty($searchItem);

        // create new item
        $item = $this->createItem($entityManager);

        // ensure appropriate search item has been created
        $searchItem = $searchItemRepository->findOneBy(['title' => self::ENTITY_TITLE]);
        $this->assertNotEmpty($searchItem);
        // ensure appropriate search index has been created
        $searchIndex = $searchIndexRepository->findOneBy(['item' => $searchItem, 'field' => 'all_text']);
        $this->assertNotEmpty($searchIndex);
        $this->assertContains(self::ENTITY_TITLE, $searchIndex->getValue());

        // update test item
        $newTitle          = self::ENTITY_TITLE . '-new';
        $item->stringValue = $newTitle;
        $entityManager->persist($item);
        $entityManager->flush();

        // ensure appropriate search item has been updated
        $searchItem = $searchItemRepository->findOneBy(['title' => $newTitle]);
        $this->assertNotEmpty($searchItem);
        // ensure appropriate search index has been updated
        $searchIndex = $searchIndexRepository->findOneBy(['item' => $searchItem, 'field' => 'all_text']);
        $this->assertNotEmpty($searchIndex);
        $this->assertContains($newTitle, $searchIndex->getValue());

        // remove created item
        $entityManager->remove($item);
        $entityManager->flush();

        // ensure appropriate search items has been deleted
        $this->assertNull($searchItem->getId());
        $this->assertNull($searchIndex->getId());
        $searchItem = $searchItemRepository->findOneBy(['title' => $newTitle]);
        $this->assertEmpty($searchItem);
    }

    /**
     * Important note: this test relies on OroB2BCMSBundle's fixtures
     * imported within the installation process by default.
     */
    public function testSelectingAdditionalColumnsFromIndex()
    {
        $managerRegistry = $this->getContainer()->get('doctrine');

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchMappingProvider = new SearchMappingProvider(
            $eventDispatcher
        );

        $objectMapper = new ObjectMapper(
            $eventDispatcher,
            []
        );

        $objectMapper->setMappingProvider($searchMappingProvider);

        $ormEngine = new Orm(
            $managerRegistry,
            $eventDispatcher,
            $doctrineHelper,
            $objectMapper
        );

        $query = new Query();

        $query->addSelect('currentSlug', Query::TYPE_TEXT);
        $query->from('orob2b_cms_page');

        $result = $ormEngine->search($query);

        $this->assertNotEmpty($result);
        $this->assertInstanceOf(Result::class, $result);

        $elements = $result->getElements();

        $this->assertNotEmpty($elements);

        foreach ($elements as $item) {
            $selectedData = $item->getSelectedData();
            $this->assertNotEmpty($selectedData);
            $this->assertArrayHasKey('currentSlug', $selectedData);
        }
    }

    /**
     * @param  EntityManager $entityManager
     * @return Item
     */
    protected function createItem(EntityManager $entityManager)
    {
        $item              = new Item();
        $item->stringValue = self::ENTITY_TITLE;

        $entityManager->persist($item);
        $entityManager->flush();

        return $item;
    }
}
