<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Engine\ExtendedEngineInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Security\SecurityProvider;
use Oro\Bundle\SearchBundle\Test\Unit\SearchMappingTypeCastingHandlersTestTrait;
use Oro\Bundle\SecurityBundle\Search\AclHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class IndexerTest extends \PHPUnit\Framework\TestCase
{
    use SearchMappingTypeCastingHandlersTestTrait;

    /** @var Indexer */
    protected $indexService;

    /** @var ObjectMapper|\PHPUnit\Framework\MockObject\MockObject */
    protected $mapper;

    /** @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $engine;

    /** @var SecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $securityProvider;

    /** @var array */
    protected $config;

    protected function setUp(): void
    {
        $this->config = require rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'searchConfig.php';
        $this->engine = $this->createMock(ExtendedEngineInterface::class);

        $configProvider = $this->createMock(MappingConfigurationProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->config);
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->any())
            ->method('fetch')
            ->willReturn(false);
        /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();
        $mapperProvider = new SearchMappingProvider($eventDispatcher, $configProvider, $cache, 'test', 'test');

        $this->securityProvider = $this->getMockBuilder('Oro\Bundle\SearchBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()->getMock();
        $this->securityProvider->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));
        $this->securityProvider->expects($this->any())
            ->method('isProtectedEntity')
            ->will($this->returnValue(true));

        /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject $searchAclHelper */
        $searchAclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Search\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $searchAclHelper->expects($this->any())
            ->method('apply')
            ->willReturnCallback(
                function ($query) {
                    return $query;
                }
            );

        $this->mapper = new ObjectMapper(
            $mapperProvider,
            PropertyAccess::createPropertyAccessor(),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(HtmlTagHelper::class)
        );
        $this->mapper->setTypeCastingHandlerRegistry($this->getTypeCastingHandlerRegistry());

        $this->indexService = new Indexer(
            $this->engine,
            $this->mapper,
            $this->securityProvider,
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
            ->will(
                $this->returnCallback(
                    function (Query $query) use ($searchResults) {
                        return new Result($query, $searchResults, count($searchResults));
                    }
                )
            );

        $result = $this->indexService->query($select);
        $this->assertEquals($searchResults, $result->getElements());
        $this->assertEquals(count($searchResults), $result->getRecordsCount());
    }

    /**
     * @return array
     */
    public function simpleSearchDataProvider()
    {
        return [
            'no extra parameters' => [
                'expectedQuery' => 'from * where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
            ],
            'custom offset' => [
                'expectedQuery' => 'from * where text all_text ~ "qwerty" offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
            ],
            'custom offset custom maxResults' => [
                'expectedQuery' => 'from * where text all_text ~ "qwerty" limit 200 offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
            ],
            'custom from' => [
                'expectedQuery' => 'from test_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => 0,
                'maxResults'    => 0,
                'from'          => 'test_customer',
            ],
            'all custom parameters' => [
                'expectedQuery' => 'from test_customer where text all_text ~ "qwerty" limit 200 offset 400',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
                'from'          => 'test_customer',
                'page'          => 3,
            ],
            'search by inherited entity' => [
                'expectedQuery' => 'from concrete_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'concrete_customer',
            ],
            'search by superclass entity, mode including descendants' => [
                'expectedQuery' => 'from customer, concrete_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'customer',
            ],
            'search by abstract entity, mode descendants only' => [
                'expectedQuery' => 'from repeatable_task, scheduled_task where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'task',
            ],
            'unknown from' => [
                'expectedQuery' => 'from unknown_entity where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => 0,
                'maxResults'    => 0,
                'from'          => 'unknown_entity',
            ],
        ];
    }

    /**
     * @param string $expectedQuery
     * @param string $string
     * @param int $offset
     * @param int $maxResults
     * @param null $from
     * @param int $page
     * @dataProvider simpleSearchDataProvider
     */
    public function testSimpleSearch($expectedQuery, $string, $offset = 0, $maxResults = 0, $from = null, $page = 0)
    {
        $searchResults = ['one', 'two', 'three'];

        $this->engine->expects($this->any())
            ->method('search')
            ->will(
                $this->returnCallback(
                    function (Query $query) use ($searchResults) {
                        return new Result($query, $searchResults, count($searchResults));
                    }
                )
            );

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
            ->will(
                $this->returnCallback(
                    function (Query $query) use ($searchResults) {
                        return new Result($query, $searchResults, count($searchResults));
                    }
                )
            );

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
