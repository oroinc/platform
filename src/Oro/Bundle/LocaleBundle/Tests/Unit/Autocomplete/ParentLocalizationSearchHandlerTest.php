<?php

namespace Oro\Bundle\localeBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\LocaleBundle\Autocomplete\ParentLocalizationSearchHandler;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SearchBundle\Engine\Indexer;

use Oro\Component\Testing\Unit\EntityTrait;

class ParentLocalizationSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TEST_ENTITY_CLASS = 'stdClass';

    /** @var Indexer|\PHPUnit_Framework_MockObject_MockObject */
    protected $indexer;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var ParentLocalizationSearchHandler */
    protected $searchHandler;

    protected function setUp()
    {
        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchHandler = new ParentLocalizationSearchHandler(self::TEST_ENTITY_CLASS, ['name']);
        $this->searchHandler->initSearchIndexer($this->indexer, [self::TEST_ENTITY_CLASS => ['alias' => 'alias']]);
        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->getManagerRegistryMock());
    }

    public function testSearchNoSeparator()
    {
        $this->indexer->expects($this->never())->method($this->anything());

        $result = $this->searchHandler->search('test', 1, 10);

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('more', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertFalse($result['more']);
        $this->assertEmpty($result['results']);
    }

    /**
     * @dataProvider searchDataProvider
     *
     * @param string $search
     * @param int|null $entityId
     * @param object|null $entity
     * @param array $foundElements
     * @param array $resultData
     * @param array $expectedIds
     */
    public function testSearch($search, $entityId, $entity, array $foundElements, array $resultData, array $expectedIds)
    {
        $page = 1;
        $perPage = 15;

        $foundElements = array_map(
            function ($id) {
                $element = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
                    ->disableOriginalConstructor()
                    ->getMock();
                $element->expects($this->once())->method('getRecordId')->willReturn($id);

                return $element;
            },
            $foundElements
        );

        $this->assertSearchCall($search, $page, $perPage, $foundElements, $resultData, $expectedIds);

        $this->entityRepository->expects($this->any())->method('find')->with($entityId)->willReturn($entity);

        $searchResult = $this->searchHandler->search(
            sprintf('%s%s%s', $search, ParentLocalizationSearchHandler::DELIMITER, $entityId),
            $page,
            $perPage
        );

        $this->assertInternalType('array', $searchResult);
        $this->assertArrayHasKey('more', $searchResult);
        $this->assertArrayHasKey('results', $searchResult);

        $expectedResultData = array_map(
            function ($id) {
                return ['id' => $id, 'name' => 'test' . $id];
            },
            $expectedIds
        );

        $this->assertEquals($expectedResultData, $searchResult['results']);
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        $local6 = $this->getLocalization(6, 'test6');
        $local5 = $this->getLocalization(5, 'test5');

        $local4 = $this->getLocalization(4, 'test4');
        $local4->addChildLocalization($local5);
        $local4->addChildLocalization($local6);

        $local3 = $this->getLocalization(3, 'test3');

        $local42 = $this->getLocalization(42, 'test42');
        $local42->addChildLocalization($local3);
        $local42->addChildLocalization($local4);

        $local100 = $this->getLocalization(100, 'test100');

        return [
            'without entity' => [
                'query' => 'test',
                'entityId' => null,
                'entity' => null,
                'foundElements' => [100, 42, 3, 4, 5, 6],
                'resultData' => [$local100, $local42, $local3, $local4, $local5, $local6],
                'expectedIds' => [100, 42, 3, 4, 5, 6]
            ],
            'with entity and with children' => [
                'query' => 'test',
                'entityId' => 42,
                'entity' => $local42,
                'foundElements' => [100, 42, 3, 4, 5, 6],
                'resultData' => [$local100],
                'expectedIds' => [100]
            ],
            'with entity and without children' => [
                'query' => 'test',
                'entityId' => 100,
                'entity' => $local100,
                'foundElements' => [42, 3, 4, 5, 6, 100],
                'resultData' => [$local42, $local3, $local4, $local5, $local6],
                'expectedIds' => [42, 3, 4, 5, 6]
            ]
        ];
    }

    /**
     * @param int $id
     * @param string $name
     * @return Localization
     */
    protected function getLocalization($id, $name)
    {
        return $this->getEntity('Oro\Bundle\LocaleBundle\Entity\Localization', ['id' => $id, 'name' => $name]);
    }

    /**
     * @param string $search
     * @param int $page
     * @param int $perPage
     * @param array $foundElements
     * @param array $resultData
     * @param array $expectedIds
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertSearchCall(
        $search,
        $page,
        $perPage,
        array $foundElements,
        array $resultData,
        array $expectedIds
    ) {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())->method('getResult')->will($this->returnValue($resultData));

        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')->disableOriginalConstructor()->getMock();
        $expr->expects($this->once())->method('in')->with('e.id', $expectedIds)->willReturnSelf();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $queryBuilder->expects($this->once())->method('expr')->willReturn($expr);
        $queryBuilder->expects($this->once())->method('where')->with($expr)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $this->entityRepository->expects($this->any())->method('createQueryBuilder')->willReturn($queryBuilder);

        $searchResult = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result')
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult->expects($this->once())->method('getElements')->willReturn($foundElements);

        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with($search, $page - 1, $perPage + 1, 'alias')
            ->willReturn($searchResult);

        return $searchResult;
    }

    /**
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManagerRegistryMock()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($this->getMetadataFactoryMock());
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->entityRepository);

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($entityManager);

        return $managerRegistry;
    }

    /**
     * @return ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMetadataFactoryMock()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())->method('getSingleIdentifierFieldName')->willReturn('id');

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($metadata);

        return $metadataFactory;
    }
}
