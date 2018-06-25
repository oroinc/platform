<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\FormBundle\Tests\Unit\MockHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

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

    /**
     * @var array
     */
    protected $testProperties = ['name', 'email', 'property.path'];

    /**
     * @var array
     */
    protected $testSearchConfig = [self::TEST_ENTITY_CLASS => ['alias' => self::TEST_ENTITY_SEARCH_ALIAS]];

    /**
     * @var Indexer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $indexer;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $managerRegistry;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityRepository;

    /**
     * @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $queryBuilder;

    /**
     * @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $query;

    /**
     * @var Expr|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $expr;

    /**
     * @var Result|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $searchResult;

    /**
     * @var SearchHandler
     */
    protected $searchHandler;

    /**
     * @var AclHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->indexer = $this->getMockBuilder(Indexer::class)
            ->setMethods(['simpleSearch'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder'])
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->setMethods(['getSingleIdentifierFieldName'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue(self::TEST_ID_FIELD));

        $metadataFactory = $this->getMockBuilder(ClassMetadataFactory::class)
            ->setMethods(['getMetadataFor'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($metadata));

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'getMetadataFactory'])
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
            ->setMethods(['expr', 'getQuery', 'where'])
            ->getMock();

        $this->query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResult', 'getAST'])
            ->getMockForAbstractClass();

        $this->expr = $this->getMockBuilder(Expr::class)
            ->disableOriginalConstructor()
            ->setMethods(['in'])
            ->getMock();

        $this->searchResult = $this->getMockBuilder(Result::class)
            ->setMethods(['getElements'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder(AclHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['apply'])
            ->getMock();

        $this->searchHandler = new SearchHandler(
            self::TEST_ENTITY_CLASS,
            $this->testProperties
        );

        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->managerRegistry);
        $this->searchHandler->initSearchIndexer($this->indexer, $this->testSearchConfig);
        $this->searchHandler->setAclHelper($this->aclHelper);
    }

    public function testConstructorAndInitialize()
    {
        $this->assertAttributeSame(
            $this->indexer,
            'indexer',
            $this->searchHandler
        );
        $this->assertAttributeSame(
            $this->entityRepository,
            'entityRepository',
            $this->searchHandler
        );
        $this->assertAttributeEquals(
            self::TEST_ENTITY_CLASS,
            'entityName',
            $this->searchHandler
        );
        $this->assertAttributeEquals(
            self::TEST_ID_FIELD,
            'idFieldName',
            $this->searchHandler
        );
        $this->assertAttributeEquals(
            $this->testProperties,
            'properties',
            $this->searchHandler
        );
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
                    ['where', ['e.id IN (1, 2, 3, 4)'], 'getMockQueryBuilder'],
                    ['getQuery', [], 'getMockQuery']
                ],
                'expectExprCalls' => [
                    ['in', ['e.' . self::TEST_ID_FIELD, [1, 2, 3, 4]], 'e.id IN (1, 2, 3, 4)'],
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
                    ['where', ['e.id IN (1, 2)'], 'getMockQueryBuilder'],
                    ['getQuery', [], 'getMockQuery']
                ],
                'expectExprCalls' => [
                    ['in', ['e.' . self::TEST_ID_FIELD, [1, 2]], 'e.id IN (1, 2)'],
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
     * @return Result|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockSearchResult()
    {
        return $this->searchResult;
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return Expr|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockExpr()
    {
        return $this->expr;
    }

    /**
     * @return AbstractQuery|\PHPUnit\Framework\MockObject\MockObject
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
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function createMockEntity(array $data)
    {
        $methods = [];
        foreach (array_keys($data) as $name) {
            $methods[$name] = 'get' . ucfirst($name);
        }
        $result = $this->createPartialMock(\stdClass::class, array_values($methods));
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
     * @param \stdClass $stdClass
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
            ->with($expr->in('e.id', $searchIds));

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
            ->with($expr->in('e.id', $searchIds));

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
