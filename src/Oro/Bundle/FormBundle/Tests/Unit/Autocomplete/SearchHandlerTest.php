<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ID_FIELD = 'id';
    private const TEST_ENTITY_CLASS = 'FooEntityClass';
    private const TEST_ENTITY_SEARCH_ALIAS = 'foo_entity';

    /** @var array */
    private $testProperties = ['name', 'email', 'property.path'];

    /** @var array */
    private $testSearchConfig = [self::TEST_ENTITY_CLASS => ['alias' => self::TEST_ENTITY_SEARCH_ALIAS]];

    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    private $indexer;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var Expr|\PHPUnit\Framework\MockObject\MockObject */
    private $expr;

    /** @var Result|\PHPUnit\Framework\MockObject\MockObject */
    private $searchResult;

    /** @var SearchHandler */
    private $searchHandler;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->indexer = $this->createMock(Indexer::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);
        $this->expr = $this->createMock(Expr::class);
        $this->searchResult = $this->createMock(Result::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn(self::TEST_ID_FIELD);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($metadata);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->entityRepository);
        $entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($entityManager);

        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->testSearchConfig[self::TEST_ENTITY_CLASS]['alias']);

        $this->searchHandler = new SearchHandler(self::TEST_ENTITY_CLASS, $this->testProperties);

        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($doctrine);
        $this->searchHandler->initSearchIndexer($this->indexer, $searchMappingProvider);
        $this->searchHandler->setAclHelper($this->aclHelper);
        $this->searchHandler->setLogger($this->logger);
        $this->searchHandler->setPropertyAccessor(PropertyAccess::createPropertyAccessor());
    }

    private function createStdClass(array $properties): \stdClass
    {
        $result = new \stdClass();
        foreach ($properties as $name => $value) {
            $result->{$name} = $value;
        }

        return $result;
    }

    private function createMockEntity(array $data): \stdClass
    {
        $methods = [];
        foreach (array_keys($data) as $name) {
            $methods[$name] = 'get' . ucfirst($name);
        }
        $result = $this->getMockBuilder(\stdClass::class)
            ->addMethods($methods)
            ->getMock();
        foreach ($data as $name => $property) {
            $result->expects($this->any())
                ->method($methods[$name])
                ->willReturn($property);
        }

        return $result;
    }

    private function createMockSearchItems(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $item = $this->createMock(Item::class);
            $item->expects($this->once())
                ->method('getRecordId')
                ->willReturn($id);
            $result[] = $item;
        }

        return $result;
    }

    private function createStubEntityWithProperties(array $data): \stdClass
    {
        $result = new \stdClass();
        foreach ($data as $name => $property) {
            if (is_array($property)) {
                foreach ($property as $propertyKey => $propertyValue) {
                    if (!isset($result->{$name})) {
                        $result->{$name} = new \stdClass();
                    }
                    $result->{$name}->{$propertyKey} = $propertyValue;
                }
            } else {
                $result->{$name} = $property;
            }
        }

        return $result;
    }

    public function testGetProperties()
    {
        $this->assertEquals($this->testProperties, $this->searchHandler->getProperties());
    }

    public function testGetEntityName()
    {
        $this->assertEquals(self::TEST_ENTITY_CLASS, $this->searchHandler->getEntityName());
    }

    public function testSearchDefault()
    {
        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with('search', 0, 101, self::TEST_ENTITY_SEARCH_ALIAS)
            ->willReturn($this->searchResult);
        $this->searchResult->expects($this->once())
            ->method('getElements')
            ->willReturn($this->createMockSearchItems([1, 2, 3, 4]));
        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($this->expr);
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.id IN :entityIds')
            ->willReturnSelf();
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', [1, 2, 3, 4])
            ->willReturnSelf();
        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->expr->expects($this->once())
            ->method('in')
            ->with('e.' . self::TEST_ID_FIELD, ':entityIds')
            ->willReturn('e.id IN :entityIds');
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([
                /**
                 * test sorting works correct
                 */
                [self::TEST_ID_FIELD => 3, 'name' => 'Jack'],
                $this->createMockEntity(
                    [self::TEST_ID_FIELD => 1, 'name' => 'John', 'email' => 'john@example.com']
                ),
                $this->createMockEntity(
                    [self::TEST_ID_FIELD => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
                ),
                $this->createStubEntityWithProperties(
                    [
                        self::TEST_ID_FIELD => 4,
                        'name' => 'Bill',
                        'email' => 'bill@example.com',
                        'property' => ['path' => 'value'],
                    ]
                ),
            ]);

        $this->logger->expects($this->never())
              ->method('critical');
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->query)
            ->willReturn($this->query);

        $actualResult = $this->searchHandler->search('search', 1, 100);
        $this->assertEquals(
            [
                'results' => [
                    [
                        self::TEST_ID_FIELD => 1,
                        'name' => 'John',
                        'email' => 'john@example.com',
                        'property.path' => null,
                    ],
                    [
                        self::TEST_ID_FIELD => 2,
                        'name' => 'Jane',
                        'email' => 'jane@example.com',
                        'property.path' => null,
                    ],
                    [self::TEST_ID_FIELD => 3, 'name' => 'Jack', 'email' => null, 'property.path' => null],
                    [
                        self::TEST_ID_FIELD => 4,
                        'name' => 'Bill',
                        'email' => 'bill@example.com',
                        'property.path' => 'value',
                    ],
                ],
                'more' => false,
            ],
            $actualResult
        );
    }

    public function testSearchHasMore()
    {
        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with('search', 0, 2, self::TEST_ENTITY_SEARCH_ALIAS)
            ->willReturn($this->searchResult);
        $this->searchResult->expects($this->once())
            ->method('getElements')
            ->willReturn($this->createMockSearchItems([1, 2]));
        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($this->expr);
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.id IN :entityIds')
            ->willReturnSelf();
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', [1, 2])
            ->willReturnSelf();
        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->expr->expects($this->once())
            ->method('in')
            ->with('e.' . self::TEST_ID_FIELD, ':entityIds')
            ->willReturn('e.id IN :entityIds');
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([
                /**
                 * test sorting works correct
                 */
                $this->createMockEntity(
                    [self::TEST_ID_FIELD => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
                ),
                $this->createMockEntity(
                    [self::TEST_ID_FIELD => 1, 'name' => 'John', 'email' => 'john@example.com']
                ),
            ]);

        $this->logger->expects($this->never())
            ->method('critical');
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->query)
            ->willReturn($this->query);

        $actualResult = $this->searchHandler->search('search', 1, 1);
        $this->assertEquals(
            [
                'results' => [
                    [
                        self::TEST_ID_FIELD => 1,
                        'name' => 'John',
                        'email' => 'john@example.com',
                        'property.path' => null,
                    ],
                ],
                'more' => true,
            ],
            $actualResult
        );
    }

    /**
     * @dataProvider convertItemProvider
     */
    public function testConvertItem(\stdClass $item, array $expectedItem)
    {
        $this->assertEquals($expectedItem, $this->searchHandler->convertItem($item));
    }

    public function convertItemProvider(): array
    {
        return [
            'missing properties' => [
                $this->createStdClass([]),
                [
                    'id' => null,
                    'name' => null,
                    'email' => null,
                    'property.path' => null,
                ],
            ],
            'null properties' => [
                $this->createStdClass([
                    'property' => null,
                    'name' => null,
                    'email' => null,
                ]),
                [
                    'id' => null,
                    'name' => null,
                    'email' => null,
                    'property.path' => null,
                ],
            ],
            'properties with values' => [
                $this->createStdClass([
                    'id' => 1,
                    'name' => 'nval',
                    'email' => 'eval',
                    'property' => $this->createStdClass([
                        'path' => 'ppval'
                    ]),
                ]),
                [
                    'id' => 1,
                    'name' => 'nval',
                    'email' => 'eval',
                    'property.path' => 'ppval',
                ],
            ],
        ];
    }

    public function testSearchByIds()
    {
        $searchQuery = '1,2';
        $searchIds = ['1', '2'];
        $expected = [
            'results' => [
                [self::TEST_ID_FIELD => 1, 'name' => 'Jane1', 'email' => 'jane1@example.com', 'property.path' => null],
                [self::TEST_ID_FIELD => 2, 'name' => 'Jane2', 'email' => 'jane2@example.com', 'property.path' => null]
            ],
            'more' => false
        ];

        $expr = new Expr();
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('where')
            ->with($expr->in('e.id', ':entityIds'))
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', $searchIds)
            ->willReturnSelf();

        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $result = [
            $this->createMockEntity([self::TEST_ID_FIELD => 1, 'name' => 'Jane1', 'email' => 'jane1@example.com']),
            $this->createMockEntity([self::TEST_ID_FIELD => 2, 'name' => 'Jane2', 'email' => 'jane2@example.com'])
        ];
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($query)
            ->willReturn($query);

        $this->assertEquals($expected, $this->searchHandler->search($searchQuery, 1, 10, true));
    }

    public function testSearchByIdsExceptionLogged()
    {
        $searchQuery = 'some-wrong-query-string';
        $searchIds = ['some-wrong-query-string'];
        $expected = [
            'results' => [],
            'more' => false
        ];

        $expr = new Expr();
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('where')
            ->with($expr->in('e.id', ':entityIds'))
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', $searchIds)
            ->willReturnSelf();

        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willThrowException(new \Exception('Some exception message'));

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($query)
            ->willReturn($query);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Some exception message');

        $this->assertEquals($expected, $this->searchHandler->search($searchQuery, 1, 10, true));
    }

    public function testSearchByIdsWithEmptyString()
    {
        $searchQuery = '1,,2';
        $searchIds = [0 => '1', 2 => '2'];
        $expected = [
            'results' => [
                [self::TEST_ID_FIELD => 1, 'name' => 'Jane1', 'email' => 'jane1@example.com', 'property.path' => null],
                [self::TEST_ID_FIELD => 2, 'name' => 'Jane2', 'email' => 'jane2@example.com', 'property.path' => null]
            ],
            'more' => false
        ];

        $expr = new Expr();
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('where')
            ->with($expr->in('e.id', ':entityIds'))
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', $searchIds)
            ->willReturnSelf();

        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $result = [
            $this->createMockEntity([self::TEST_ID_FIELD => 1, 'name' => 'Jane1', 'email' => 'jane1@example.com']),
            $this->createMockEntity([self::TEST_ID_FIELD => 2, 'name' => 'Jane2', 'email' => 'jane2@example.com'])
        ];
        $query = $this->createMock(AbstractQuery::class);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($query)
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->assertEquals($expected, $this->searchHandler->search($searchQuery, 1, 10, true));
    }

    public function testSearchByIdsEmptySearch()
    {
        $searchQuery = '';
        $expected = [
            'results' => [],
            'more' => false
        ];

        $this->entityRepository->expects($this->never())
            ->method('createQueryBuilder');

        $this->assertEquals($expected, $this->searchHandler->search($searchQuery, 1, 10, true));
    }

    public function testSearchByIdsIncorrectSearchString()
    {
        $searchQuery = ',';
        $expected = [
            'results' => [],
            'more' => false
        ];

        $this->entityRepository->expects($this->never())
            ->method('createQueryBuilder');

        $this->assertEquals($expected, $this->searchHandler->search($searchQuery, 1, 10, true));
    }
}
