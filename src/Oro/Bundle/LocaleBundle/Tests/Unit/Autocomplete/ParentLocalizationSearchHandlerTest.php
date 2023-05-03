<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Autocomplete\ParentLocalizationSearchHandler;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Component\Testing\ReflectionUtil;

class ParentLocalizationSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ENTITY_CLASS = 'stdClass';

    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    private $indexer;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var ParentLocalizationSearchHandler */
    private $searchHandler;

    protected function setUp(): void
    {
        $this->indexer = $this->createMock(Indexer::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn('alias');

        $this->searchHandler = new ParentLocalizationSearchHandler(self::TEST_ENTITY_CLASS, ['name']);
        $this->searchHandler->initSearchIndexer($this->indexer, $searchMappingProvider);
        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->getManagerRegistry());
        $this->searchHandler->setPropertyAccessor(PropertyAccess::createPropertyAccessor());
    }

    public function testSearchNoDelimiter()
    {
        $this->indexer->expects($this->never())
            ->method($this->anything());

        $result = $this->searchHandler->search('test', 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('more', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertFalse($result['more']);
        $this->assertEmpty($result['results']);
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch(
        string $search,
        ?int $entityId,
        ?object $entity,
        array $foundElements,
        array $resultData,
        array $expectedIds,
        bool $byId = false
    ) {
        $page = 1;
        $perPage = 15;

        $foundElements = array_map(
            function ($id) {
                $element = $this->createMock(Item::class);
                $element->expects($this->any())
                    ->method('getRecordId')
                    ->willReturn($id);

                return $element;
            },
            $foundElements
        );

        $this->assertSearchCall($search, $page, $perPage, $foundElements, $resultData, $expectedIds, $byId);

        $this->entityRepository->expects($this->any())
            ->method('find')
            ->with($entityId)
            ->willReturn($entity);

        $searchResult = $this->searchHandler->search(
            sprintf('%s%s%s', $search, ParentLocalizationSearchHandler::DELIMITER, $entityId),
            $page,
            $perPage,
            $byId
        );

        $this->assertIsArray($searchResult);
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

    public function searchDataProvider(): array
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
            ],
            'by id' => [
                'query' => '42',
                'entityId' => null,
                'entity' => $local42,
                'foundElements' => [42],
                'resultData' => [$local42],
                'expectedIds' => [42],
                'byId' => true
            ]
        ];
    }

    private function getLocalization(int $id, string $name): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);
        $localization->setName($name);

        return $localization;
    }

    private function assertSearchCall(
        string $search,
        int $page,
        int $perPage,
        array $foundElements,
        array $resultData,
        array $expectedIds,
        bool $byId = false
    ): void {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($resultData);

        $expr = $this->createMock(Expr::class);
        $expr->expects($this->once())
            ->method('in')
            ->with('e.id', ':entityIds')
            ->willReturnSelf();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with($expr)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', $expectedIds)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->entityRepository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $searchResult = $this->createMock(Result::class);
        $searchResult->expects($this->any())
            ->method('getElements')
            ->willReturn($foundElements);

        if ($byId) {
            $this->indexer->expects($this->never())
                ->method($this->anything());
        } else {
            $this->indexer->expects($this->once())
                ->method('simpleSearch')
                ->with($search, $page - 1, $perPage + 1, 'alias')
                ->willReturn($searchResult);
        }
    }

    private function getManagerRegistry(): ManagerRegistry
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($this->getMetadataFactory());
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->entityRepository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($entityManager);

        return $managerRegistry;
    }

    private function getMetadataFactory(): ClassMetadataFactory
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($metadata);

        return $metadataFactory;
    }
}
