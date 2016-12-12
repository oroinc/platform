<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Item2;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group search
 * @dbIsolation
 */
class OrmIndexerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_search.engine') != 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }

    public function testShouldCreateIndexForEntity()
    {
        $itemManager = $this->getDoctrine()->getManagerForClass(Item::class);

        $indexManager = $this->getDoctrine()->getManagerForClass(IndexText::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);
        $indexTextRepository = $indexManager->getRepository(IndexText::class);

        $item = new Item();
        $item->stringValue = 'value';

        $itemManager->persist($item);
        $itemManager->flush();

        // guard
        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertEmpty($itemIndex);

        // test
        $this->getSearchIndexer()->save($item);

        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertNotEmpty($itemIndex);

        $itemTextIndex = $indexTextRepository->findOneBy(['item' => $itemIndex, 'field' => 'all_text']);
        $this->assertNotEmpty($itemTextIndex);
        $this->assertEquals('value', $itemTextIndex->getValue());
    }

    public function testShouldUpdateIndexForEntity()
    {
        $itemManager = $this->getDoctrine()->getManagerForClass(Item::class);

        $indexManager = $this->getDoctrine()->getManagerForClass(IndexText::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);
        $indexTextRepository = $indexManager->getRepository(IndexText::class);

        $item = new Item();
        $item->stringValue = 'value';

        $itemManager->persist($item);
        $itemManager->flush();

        // create
        $this->getSearchIndexer()->save($item);

        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertNotEmpty($itemIndex);

        $itemTextIndex = $indexTextRepository->findOneBy(['item' => $itemIndex, 'field' => 'all_text']);
        $this->assertNotEmpty($itemTextIndex);
        $this->assertEquals('value', $itemTextIndex->getValue());

        // update
        $item->stringValue = 'newvalue';

        $itemManager->persist($item);
        $itemManager->flush();

        $this->getSearchIndexer()->save($item);

        $itemTextIndex = $indexTextRepository->findOneBy(['item' => $itemIndex, 'field' => 'all_text']);
        $this->assertNotEmpty($itemTextIndex);
        $this->assertEquals('newvalue', $itemTextIndex->getValue());
    }

    public function testShouldDeleteIndexForEntity()
    {
        /** @var EntityManager $itemManager */
        $itemManager = $this->getDoctrine()->getManagerForClass(Item::class);

        $indexManager = $this->getDoctrine()->getManagerForClass(IndexText::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);
        $indexTextRepository = $indexManager->getRepository(IndexText::class);

        $item = new Item();
        $item->stringValue = 'value';

        $itemManager->persist($item);
        $itemManager->flush();

        // create
        $this->getSearchIndexer()->save($item);

        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertNotEmpty($itemIndex);

        $itemTextIndex = $indexTextRepository->findOneBy(['item' => $itemIndex, 'field' => 'all_text']);
        $this->assertNotEmpty($itemTextIndex);

        $itemId = $item->getId();
        $itemTextIndexId = $itemTextIndex->getId();

        // delete
        $itemManager->remove($item);
        $itemManager->flush();

        $item = $itemManager->getReference(Item::class, $itemId);

        $this->getSearchIndexer()->delete($item);

        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $itemTextIndex = $indexTextRepository->find($itemTextIndexId);

        $this->assertEmpty($itemIndex);
        $this->assertEmpty($itemTextIndex);
    }

    public function testShouldReturnAllClassesForReindex()
    {
        $classes = $this->getSearchIndexer()->getClassesForReindex();

        $this->assertContains(Item::class, $classes);
        $this->assertContains(Item2::class, $classes);
    }

    public function testShouldReturnClassesForReindexForClass()
    {
        $classes = $this->getSearchIndexer()->getClassesForReindex(Item::class);

        $this->assertCount(1, $classes);
        $this->assertEquals(Item::class, $classes[0]);
    }

    public function testShouldReindexAllClasses()
    {
        $itemManager = $this->getDoctrine()->getManagerForClass(Item::class);

        $indexManager = $this->getDoctrine()->getManagerForClass(IndexText::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);

        $item = new Item();

        $itemManager->persist($item);
        $itemManager->flush();

        // guard
        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertEmpty($itemIndex);

        // test
        $this->getSearchIndexer()->reindex();
        $itemIndex = $indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]);
        $this->assertNotEmpty($itemIndex);
    }

    public function testShouldReindexEntitiesOnlyOfSingleClass()
    {
        $itemManager = $this->getDoctrine()->getManagerForClass(Item::class);

        $indexManager = $this->getDoctrine()->getManagerForClass(IndexText::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);

        $item = new Item();
        $item2 = new Item2();

        $itemManager->persist($item);
        $itemManager->persist($item2);
        $itemManager->flush();

        // guard
        $this->assertEmpty($indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]));
        $this->assertEmpty($indexItemRepository->findOneBy(['entity' => Item2::class, 'recordId' => $item2->getId()]));

        // test
        // reindex first one
        $this->getSearchIndexer()->reindex(Item::class);

        $this->assertNotEmpty($indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]));
        $this->assertEmpty($indexItemRepository->findOneBy(['entity' => Item2::class, 'recordId' => $item2->getId()]));

        // reindex second
        $this->getSearchIndexer()->reindex(Item2::class);

        $this->assertNotEmpty($indexItemRepository->findOneBy(['entity' => Item::class, 'recordId' => $item->getId()]));
        $this->assertNotEmpty(
            $indexItemRepository->findOneBy(['entity' => Item2::class, 'recordId' => $item2->getId()])
        );
    }

    public function testShouldResetAllIndexes()
    {
        $indexManager = $this->getDoctrine()->getManagerForClass(IndexText::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);

        // guard
        $this->getSearchIndexer()->reindex();
        $this->assertGreaterThan(0, count($indexItemRepository->findAll()));

        // test
        $this->getSearchIndexer()->resetIndex();
        $this->assertCount(0, $indexItemRepository->findAll());
    }

    public function testShouldResetIndexOnlyForSingleEntity()
    {
        $itemManager = $this->getDoctrine()->getManagerForClass(Item::class);

        $indexManager = $this->getDoctrine()->getManagerForClass(IndexText::class);
        $indexItemRepository = $indexManager->getRepository(IndexItem::class);

        $item = new Item();
        $itemManager->persist($item);
        $itemManager->flush();

        // guard
        $this->getSearchIndexer()->reindex();
        $this->assertGreaterThan(0, count($indexItemRepository->findBy(['entity' => Item::class])));

        // test
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->assertCount(0, $indexItemRepository->findBy(['entity' => Item::class]));
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return \Oro\Bundle\SearchBundle\Engine\OrmIndexer
     */
    protected function getSearchIndexer()
    {
        return $this->getContainer()->get('oro_search.search.engine.indexer');
    }
}
