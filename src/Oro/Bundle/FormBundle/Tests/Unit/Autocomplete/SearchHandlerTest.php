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
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\FormBundle\Tests\Unit\MockHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ID_FIELD = 'id';
    const TEST_ENTITY_CLASS = 'FooEntityClass';
    const TEST_ENTITY_SEARCH_ALIAS = 'foo_entity';
    const TEST_SEARCH_STRING = 'test_search_string';
    const TEST_FIRST_RESULT = 30;
    const TEST_MAX_RESULTS = 10;

    /** @var array */
    protected $testProperties = ['name', 'email', 'property.path'];

    /** @var array */
    protected $testSearchConfig = [self::TEST_ENTITY_CLASS => ['alias' => self::TEST_ENTITY_SEARCH_ALIAS]];

    /** @var Indexer|MockObject */
    protected $indexer;

    /** @var ManagerRegistry|MockObject */
    protected $managerRegistry;

    /** @var EntityManager|MockObject */
    protected $entityManager;

    /** @var EntityRepository|MockObject */
    protected $entityRepository;

    /** @var QueryBuilder|MockObject */
    protected $queryBuilder;

    /** @var AbstractQuery|MockObject */
    protected $query;

    /** @var Expr|MockObject */
    protected $expr;

    /** @var Result|MockObject */
    protected $searchResult;

    /** @var SearchHandler */
    protected $searchHandler;

    /** @var AclHelper|MockObject */
    protected $aclHelper;

    /** @var LoggerInterface|MockObject */
    protected $logger;

    protected function setUp(): void
    {
        $this->indexer = $this->getMockBuilder(Indexer::class)
            ->onlyMethods(['simpleSearch'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->onlyMethods(['getSingleIdentifierFieldName'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue(self::TEST_ID_FIELD));

        $metadataFactory = $this->getMockBuilder(ClassMetadataFactory::class)
            ->onlyMethods(['getMetadataFor'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($metadata));

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository', 'getMetadataFactory'])
            ->getMock();
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->entityRepository));
        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($this->entityManager));

        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['expr', 'getQuery', 'where', 'setParameter'])
            ->getMock();

        $this->query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->addMethods(['getAST'])
            ->getMockForAbstractClass();

        $this->expr = $this->getMockBuilder(Expr::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['in'])
            ->getMock();

        $this->searchResult = $this->getMockBuilder(Result::class)
            ->onlyMethods(['getElements'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder(AclHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['apply'])
            ->getMock();

        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->testSearchConfig[self::TEST_ENTITY_CLASS]['alias']);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->searchHandler = new SearchHandler(self::TEST_ENTITY_CLASS, $this->testProperties);

        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->managerRegistry);
        $this->searchHandler->initSearchIndexer($this->indexer, $searchMappingProvider);
        $this->searchHandler->setAclHelper($this->aclHelper);
        $this->searchHandler->setLogger($this->logger);
    }

    public function testGetProperties()
    {
        $this->assertEquals($this->testProperties, $this->searchHandler->getProperties());
    }

    public function testGetEntitName()
    {
        $this->assertEquals(self::TEST_ENTITY_CLASS, $this->searchHandler->getEntityName());
    }

    /**
     * @dataProvider searchDataProvider
     * @param string $query
     * @param array $expectedResult
     * @param array $expectedIndexerCalls
     * @param array $expectSearchResultCalls
     * @param array $expectEntityRepositoryCalls
     * @param array $expectQueryBuilderCalls
     * @param array $expectExprCalls
     * @param array $expectQueryCalls
     */
    public function testSearch(
        $query,
        $expectedResult,
        $expectedIndexerCalls,
        $expectSearchResultCalls,
        $expectEntityRepositoryCalls,
        $expectQueryBuilderCalls,
        $expectExprCalls,
        $expectQueryCalls
    ) {
        MockHelper::addMockExpectedCalls($this->indexer, $expectedIndexerCalls, $this);
        MockHelper::addMockExpectedCalls($this->searchResult, $expectSearchResultCalls, $this);
        MockHelper::addMockExpectedCalls($this->entityRepository, $expectEntityRepositoryCalls, $this);
        MockHelper::addMockExpectedCalls($this->queryBuilder, $expectQueryBuilderCalls, $this);
        MockHelper::addMockExpectedCalls($this->expr, $expectExprCalls, $this);
        MockHelper::addMockExpectedCalls($this->query, $expectQueryCalls, $this);

        $this->logger->expects($this->never())->method('critical');
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->query)
            ->willReturn($this->query);

        $actualResult = $this->searchHandler->search($query['query'], $query['page'], $query['perPage']);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function searchDataProvider()
    {
        return [
            'default' => [
                'query' => ['query' => 'search', 'page' => 1, 'perPage' => 100],
                'expectedResult' => [
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
                'expectIndexerCalls' => [
                    [
                        'simpleSearch',
                        ['search', 0, 101, self::TEST_ENTITY_SEARCH_ALIAS],
                        'getMockSearchResult',
                    ],
                ],
                'expectSearchResultCalls' => [
                    ['getElements', [], $this->createMockSearchItems([1, 2, 3, 4])],
                ],
                'expectEntityRepositoryCalls' => [
                    ['createQueryBuilder', ['e'], 'getMockQueryBuilder'],
                ],
                'expectQueryBuilderCalls' => [
                    ['expr', [], 'getMockExpr'],
                    ['where', ['e.id IN :entityIds'], 'getMockQueryBuilder'],
                    ['setParameter', ['entityIds', [1, 2, 3, 4]], 'getMockQueryBuilder'],
                    ['getQuery', [], 'getMockQuery']
                ],
                'expectExprCalls' => [
                    ['in', ['e.' . self::TEST_ID_FIELD, ':entityIds'], 'e.id IN :entityIds'],
                ],
                'expectQueryCalls' => [
                    [
                        'getResult',
                        [],
                        [
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
                        ],
                    ],
                ],
            ],
            'hasMore' => [
                'query' => ['query' => 'search', 'page' => 1, 'perPage' => 1],
                'expectedResult' => [
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
                'expectIndexerCalls' => [
                    [
                        'simpleSearch',
                        ['search', 0, 2, self::TEST_ENTITY_SEARCH_ALIAS],
                        'getMockSearchResult',
                    ],
                ],
                'expectSearchResultCalls' => [
                    ['getElements', [], $this->createMockSearchItems([1, 2])],
                ],
                'expectEntityRepositoryCalls' => [
                    ['createQueryBuilder', ['e'], 'getMockQueryBuilder'],
                ],
                'expectQueryBuilderCalls' => [
                    ['expr', [], 'getMockExpr'],
                    ['where', ['e.id IN :entityIds'], 'getMockQueryBuilder'],
                    ['setParameter', ['entityIds', [1, 2]], 'getMockQueryBuilder'],
                    ['getQuery', [], 'getMockQuery']
                ],
                'expectExprCalls' => [
                    ['in', ['e.' . self::TEST_ID_FIELD, ':entityIds'], 'e.id IN :entityIds'],
                ],
                'expectQueryCalls' => [
                    [
                        'getResult',
                        [],
                        [
                            /**
                             * test sorting works correct
                             */
                            $this->createMockEntity(
                                [self::TEST_ID_FIELD => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
                            ),
                            $this->createMockEntity(
                                [self::TEST_ID_FIELD => 1, 'name' => 'John', 'email' => 'john@example.com']
                            ),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return Result|MockObject
     */
    public function getMockSearchResult()
    {
        return $this->searchResult;
    }

    /**
     * @return QueryBuilder|MockObject
     */
    public function getMockQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return Expr|MockObject
     */
    public function getMockExpr()
    {
        return $this->expr;
    }

    /**
     * @return AbstractQuery|MockObject
     */
    public function getMockQuery()
    {
        return $this->query;
    }

    /**
     * @param  array $ids
     * @return Item[]
     */
    public function createMockSearchItems(array $ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $item = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
                ->disableOriginalConstructor()
                ->setMethods(['getRecordId'])
                ->getMock();
            $item->expects($this->once())
                ->method('getRecordId')
                ->will($this->returnValue($id));
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return \stdClass
     */
    public function createStubEntityWithProperties(array $data)
    {
        $result = new \stdClass();
        foreach ($data as $name => $property) {
            if (is_array($property)) {
                foreach ($property as $propertyKey => $propertyValue) {
                    if (!isset($result->$name)) {
                        $result->$name = new \stdClass();
                    }

                    $result->$name->$propertyKey = $propertyValue;
                }
            } else {
                $result->$name = $property;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @return MockObject
     */
    public function createMockEntity(array $data)
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
                ->will($this->returnValue($property));
        }

        return $result;
    }

    /**
     * @dataProvider convertItemProvider
     */
    public function testConvertItem($item, $expectedItem)
    {
        $this->assertEquals($expectedItem, $this->searchHandler->convertItem($item));
    }

    public function convertItemProvider()
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
                    'property' => [
                        'path' => 'ppval'
                    ],
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

    /**
     * @param array $properties
     *
     * @return \stdClass
     */
    protected function createStdClass(array $properties)
    {
        return json_decode(json_encode($properties));
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
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
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
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
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
