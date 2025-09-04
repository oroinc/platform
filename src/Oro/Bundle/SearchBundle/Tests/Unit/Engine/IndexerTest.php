<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Engine\ExtendedEngineInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\SearchBundle\Provider\SearchMappingCacheNormalizer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Security\SecurityProvider;
use Oro\Bundle\SearchBundle\Test\Unit\SearchMappingTypeCastingHandlersTestTrait;
use Oro\Bundle\SecurityBundle\Search\AclHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IndexerTest extends TestCase
{
    use SearchMappingTypeCastingHandlersTestTrait;

    private EngineInterface&MockObject $engine;
    private array $config;
    private Indexer $indexService;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = require rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'searchConfig.php';
        $this->engine = $this->createMock(ExtendedEngineInterface::class);

        $configProvider = $this->createMock(MappingConfigurationProvider::class);
        $configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($this->config);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cache->expects(self::any())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cacheItem->expects(self::any())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem->expects(self::any())
            ->method('set')
            ->willReturn($cacheItem);
        $mappingProvider = new SearchMappingProvider(
            $this->createMock(EventDispatcher::class),
            $configProvider,
            $cache,
            new SearchMappingCacheNormalizer([], [], 'target_type'),
            'test',
            'test',
            'test'
        );

        $securityProvider = $this->createMock(SecurityProvider::class);
        $securityProvider->expects(self::any())
            ->method('isGranted')
            ->willReturn(true);
        $securityProvider->expects(self::any())
            ->method('isProtectedEntity')
            ->willReturn(true);

        $searchAclHelper = $this->createMock(AclHelper::class);
        $searchAclHelper->expects(self::any())
            ->method('apply')
            ->willReturnCallback(function ($query) {
                return $query;
            });

        $mapper = new ObjectMapper(
            $mappingProvider,
            PropertyAccess::createPropertyAccessor(),
            $this->getTypeCastingHandlerRegistry(),
            $this->createMock(EntityNameResolver::class),
            $this->createMock(DoctrineHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(HtmlTagHelper::class),
            new DateTimeFormatter()
        );

        $this->indexService = new Indexer(
            $this->engine,
            $mapper,
            $securityProvider,
            $searchAclHelper
        );
    }

    public function testSelect(): void
    {
        $query = $this->indexService->select();

        self::assertEquals($this->config, $query->getMappingConfig());
    }

    public function testQuery(): void
    {
        $select = $this->indexService->select();

        $resultItem = new Item();
        $searchResults = [$resultItem];

        $this->engine->expects(self::once())
            ->method('search')
            ->willReturnCallback(function (Query $query) use ($searchResults) {
                return new Result($query, $searchResults, count($searchResults));
            });

        $result = $this->indexService->query($select);
        self::assertEquals($searchResults, $result->getElements());
        self::assertEquals(count($searchResults), $result->getRecordsCount());
    }

    /**
     * @dataProvider simpleSearchDataProvider
     */
    public function testSimpleSearch(
        string $expectedQuery,
        string $string,
        ?int $offset,
        ?int $maxResults,
        ?string $from,
        int $page
    ): void {
        $searchResults = ['one', 'two', 'three'];

        $this->engine->expects(self::any())
            ->method('search')
            ->willReturnCallback(function (Query $query) use ($searchResults) {
                return new Result($query, $searchResults, count($searchResults));
            });

        $result = $this->indexService->simpleSearch($string, $offset, $maxResults, $from, $page);
        $actualQuery = $result->getQuery()->getStringQuery();

        if ($result->getQuery()->getFrom()) {
            self::assertEquals($searchResults, $result->getElements());
            self::assertEquals(count($searchResults), $result->getRecordsCount());
        } else {
            self::assertEmpty($result->getElements());
            self::assertEquals(0, $result->getRecordsCount());
        }

        self::assertEquals($actualQuery, $expectedQuery);
    }

    public function simpleSearchDataProvider(): array
    {
        return [
            'no extra parameters' => [
                'expectedQuery' =>
                    'select text.system_entity_name from * where text all_text ~ "qwerty"',
                'string' => 'qwerty',
                'offset' => 0,
                'maxResults' => 0,
                'from' => null,
                'page' => 0
            ],
            'custom offset' => [
                'expectedQuery' =>
                    'select text.system_entity_name from * where text all_text ~ "qwerty" offset 10',
                'string' => 'qwerty',
                'offset' => 10,
                'maxResults' => 0,
                'from' => null,
                'page' => 0
            ],
            'custom offset custom maxResults' => [
                'expectedQuery' =>
                    'select text.system_entity_name from * where text all_text ~ "qwerty" limit 200 offset 10',
                'string' => 'qwerty',
                'offset' => 10,
                'maxResults' => 200,
                'from' => null,
                'page' => 0
            ],
            'custom from' => [
                'expectedQuery' =>
                    'select text.system_entity_name from test_customer where text all_text ~ "qwerty"',
                'string' => 'qwerty',
                'offset' => 0,
                'maxResults' => 0,
                'from' => 'test_customer',
                'page' => 0
            ],
            'all custom parameters' => [
                'expectedQuery' =>
                    'select text.system_entity_name from test_customer'
                    . ' where text all_text ~ "qwerty" limit 200 offset 400',
                'string' => 'qwerty',
                'offset' => 10,
                'maxResults' => 200,
                'from' => 'test_customer',
                'page' => 3
            ],
            'search by inherited entity' => [
                'expectedQuery' =>
                    'select text.system_entity_name from concrete_customer where text all_text ~ "qwerty"',
                'string' => 'qwerty',
                'offset' => null,
                'maxResults' => null,
                'from' => 'concrete_customer',
                'page' => 0
            ],
            'search by superclass entity, mode including descendants' => [
                'expectedQuery' =>
                    'select text.system_entity_name from customer, concrete_customer'
                    . ' where text all_text ~ "qwerty"',
                'string' => 'qwerty',
                'offset' => null,
                'maxResults' => null,
                'from' => 'customer',
                'page' => 0
            ],
            'search by abstract entity, mode descendants only' => [
                'expectedQuery' =>
                    'select text.system_entity_name from repeatable_task, scheduled_task'
                    . ' where text all_text ~ "qwerty"',
                'string' => 'qwerty',
                'offset' => null,
                'maxResults' => null,
                'from' => 'task',
                'page' => 0
            ],
            'unknown from' => [
                'expectedQuery' =>
                    'select text.system_entity_name from unknown_entity where text all_text ~ "qwerty"',
                'string' => 'qwerty',
                'offset' => 0,
                'maxResults' => 0,
                'from' => 'unknown_entity',
                'page' => 0
            ],
        ];
    }

    public function testAdvancedSearch(): void
    {
        $searchResults = ['one', 'two', 'three'];

        $this->engine->expects(self::any())
            ->method('search')
            ->willReturnCallback(function (Query $query) use ($searchResults) {
                return new Result($query, $searchResults, count($searchResults));
            });

        $sourceQuery = 'from test_product'
            . ' where (name ~ "test string" and integer count > 10 and (decimal price = 10 or integer qty in (2, 5)))'
            . ' order_by decimal price desc offset 10 max_results 5';
        $expectedQuery = 'from test_product where ((integer qty in (2, 5) or decimal price = 10)'
            . ' and integer count > 10 and text name ~ "test string") order by price DESC limit 5 offset 10';

        $result = $this->indexService->advancedSearch($sourceQuery);
        $actualQuery = $result->getQuery()->getStringQuery();

        self::assertEquals($searchResults, $result->getElements());
        self::assertEquals(count($searchResults), $result->getRecordsCount());
        self::assertEquals($expectedQuery, $actualQuery);
    }
}
