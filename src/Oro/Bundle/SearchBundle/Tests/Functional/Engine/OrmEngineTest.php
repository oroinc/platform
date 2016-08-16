<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;
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

        $this->loadFixtures(['Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData']);
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

    public function testSelectingAdditionalColumnsFromIndex()
    {
        $managerRegistry = $this->getContainer()->get('doctrine');

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mapper = $this->getMockBuilder('Oro\Bundle\SearchBundle\Provider\SearchMappingProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $mapper->expects($this->any())->method('getMappingConfig')
            ->will($this->returnValue([
                'Oro\Bundle\TestFrameworkBundle\Entity\Item' => [
                    'fields' => [
                        [
                            'name'          => 'stringValue',
                            'target_type'   => 'string',
                            'target_fields' => array('stringValue', 'all_data')
                        ],
                        [
                            'name'          => 'integerValue',
                            'target_type'   => 'integer',
                            'target_fields' => array('integerValue', 'all_data')
                        ]
                    ]
                ]
            ]));

        $objectMapper = new ObjectMapper(
            $eventDispatcher,
            []
        );

        $objectMapper->setMappingProvider($mapper);

        /**
         * @var EntityTitleResolverInterface $entityTitleResolver
         */
        $entityTitleResolver = $this->getMock(
            'Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface'
        );

        $ormEngine = new Orm(
            $managerRegistry,
            $eventDispatcher,
            $doctrineHelper,
            $objectMapper,
            $entityTitleResolver
        );

        $query = new Query();

        $query->addSelect('stringValue', Query::TYPE_TEXT);
        $query->from('oro_test_item');

        $result = $ormEngine->search($query);

        $this->assertNotEmpty($result);
        $this->assertInstanceOf(Result::class, $result);

        $elements = $result->getElements();

        $this->assertNotEmpty($elements);

        foreach ($elements as $item) {
            $selectedData = $item->getSelectedData();
            $this->assertNotEmpty($selectedData);
            $this->assertArrayHasKey('stringValue', $selectedData);
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
