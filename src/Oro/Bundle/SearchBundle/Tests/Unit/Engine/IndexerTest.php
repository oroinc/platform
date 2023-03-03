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
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Security\SecurityProvider;
use Oro\Bundle\SearchBundle\Test\Unit\SearchMappingTypeCastingHandlersTestTrait;
use Oro\Bundle\SecurityBundle\Search\AclHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IndexerTest extends \PHPUnit\Framework\TestCase
{
    use SearchMappingTypeCastingHandlersTestTrait;

    /** @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $engine;

    /** @var array */
    private $config;

    /** @var Indexer */
    private $indexService;

    protected function setUp(): void
    {
        $this->config = require rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'searchConfig.php';
        $this->engine = $this->createMock(ExtendedEngineInterface::class);

        $configProvider = $this->createMock(MappingConfigurationProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->config);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cache->expects($this->any())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cacheItem->expects($this->any())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem->expects($this->any())
            ->method('set')
            ->willReturn($cacheItem);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $mappingProvider = new SearchMappingProvider(
            $eventDispatcher,
            $configProvider,
            $cache,
            'test',
            'test',
            'test'
        );

        $securityProvider = $this->createMock(SecurityProvider::class);
        $securityProvider->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);
        $securityProvider->expects($this->any())
            ->method('isProtectedEntity')
            ->willReturn(true);

        $searchAclHelper = $this->createMock(AclHelper::class);
        $searchAclHelper->expects($this->any())
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

    public function testSelect()
    {
        $query = $this->indexService->select();

        $this->assertEquals($this->config, $query->getMappingConfig());
    }

    public function testQuery()
    {
        $select = $this->indexService->select();

        $resultItem = new Item();
        $searchResults = [$resultItem];

        $this->engine->expects($this->once())
            ->method('search')
            ->willReturnCallback(function (Query $query) use ($searchResults) {
                return new Result($query, $searchResults, count($searchResults));
            });

        $result = $this->indexService->query($select);
        $this->assertEquals($searchResults, $result->getElements());
        $this->assertEquals(count($searchResults), $result->getRecordsCount());
    }

    public function simpleSearchDataProvider(): array
    {
        return [
            'no extra parameters' => [
                'expectedQuery' =>
                    'select text.system_entity_name from * where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
            ],
            'custom offset' => [
                'expectedQuery' =>
                    'select text.system_entity_name from * where text all_text ~ "qwerty" offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
            ],
            'custom offset custom maxResults' => [
                'expectedQuery' =>
                    'select text.system_entity_name from * where text all_text ~ "qwerty" limit 200 offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
            ],
            'custom from' => [
                'expectedQuery' =>
                    'select text.system_entity_name from test_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => 0,
                'maxResults'    => 0,
                'from'          => 'test_customer',
            ],
            'all custom parameters' => [
                'expectedQuery' =>
                    'select text.system_entity_name from test_customer ' .
                    'where text all_text ~ "qwerty" limit 200 offset 400',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
                'from'          => 'test_customer',
                'page'          => 3,
            ],
            'search by inherited entity' => [
                'expectedQuery' =>
                    'select text.system_entity_name from concrete_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'concrete_customer',
            ],
            'search by superclass entity, mode including descendants' => [
                'expectedQuery' =>
                    'select text.system_entity_name from customer, concrete_customer ' .
                    'where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'customer',
            ],
            'search by abstract entity, mode descendants only' => [
                'expectedQuery' =>
                    'select text.system_entity_name from repeatable_task, scheduled_task ' .
                    'where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'task',
            ],
            'unknown from' => [
                'expectedQuery' =>
                    'select text.system_entity_name from unknown_entity where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => 0,
                'maxResults'    => 0,
                'from'          => 'unknown_entity',
            ],
        ];
    }

    /**
     * @dataProvider simpleSearchDataProvider
     */
    public function testSimpleSearch(
        string $expectedQuery,
        string $string,
        ?int $offset = 0,
        ?int $maxResults = 0,
        string $from = null,
        int $page = 0
    ) {
        $searchResults = ['one', 'two', 'three'];

        $this->engine->expects($this->any())
            ->method('search')
            ->willReturnCallback(function (Query $query) use ($searchResults) {
                return new Result($query, $searchResults, count($searchResults));
            });

        $result = $this->indexService->simpleSearch($string, $offset, $maxResults, $from, $page);
        $actualQuery = $result->getQuery()->getStringQuery();

        if ($result->getQuery()->getFrom()) {
            $this->assertEquals($searchResults, $result->getElements());
            $this->assertEquals(count($searchResults), $result->getRecordsCount());
        } else {
            $this->assertEmpty($result->getElements());
            $this->assertEquals(0, $result->getRecordsCount());
        }

        $this->assertEquals($actualQuery, $expectedQuery);
    }

    public function testAdvancedSearch()
    {
        $searchResults = ['one', 'two', 'three'];

        $this->engine->expects($this->any())
            ->method('search')
            ->willReturnCallback(function (Query $query) use ($searchResults) {
                return new Result($query, $searchResults, count($searchResults));
            });

        $sourceQuery = 'from test_product' .
            ' where (name ~ "test string" and integer count > 10 and (decimal price = 10 or integer qty in (2, 5)))' .
            ' order_by decimal price desc offset 10 max_results 5';
        $expectedQuery = 'from test_product where ((integer qty in (2, 5) or decimal price = 10) '
            .'and integer count > 10 and text name ~ "test string") order by price DESC limit 5 offset 10';

        $result = $this->indexService->advancedSearch($sourceQuery);
        $actualQuery = $result->getQuery()->getStringQuery();

        $this->assertEquals($searchResults, $result->getElements());
        $this->assertEquals(count($searchResults), $result->getRecordsCount());
        $this->assertEquals($expectedQuery, $actualQuery);
    }
}
