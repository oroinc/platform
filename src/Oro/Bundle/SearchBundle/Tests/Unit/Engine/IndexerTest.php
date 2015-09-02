<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class IndexerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Indexer */
    protected $indexService;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mapper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $engine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var array */
    protected $config;

    protected function setUp()
    {
        $this->config        = require rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'searchConfig.php';
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->engine        = $this->getMock('Oro\Bundle\SearchBundle\Engine\EngineInterface');
        $this->mapper        = new ObjectMapper(
            $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'),
            $this->config
        );
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();
        $mapperProvider = new SearchMappingProvider($eventDispatcher);
        $mapperProvider->setMappingConfig($this->config);
        $this->mapper->setMappingProvider($mapperProvider);

        $this->securityProvider = $this->getMockBuilder('Oro\Bundle\SearchBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()->getMock();
        $this->securityProvider->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));
        $this->securityProvider->expects($this->any())
            ->method('isProtectedEntity')
            ->will($this->returnValue(true));
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $searchAclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Search\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();

        $searchAclHelper->expects($this->any())
            ->method('apply')
            ->willReturnCallback(
                function ($query) {
                    return $query;
                }
            );

        $this->indexService = new Indexer(
            $this->entityManager,
            $this->engine,
            $this->mapper,
            $this->securityProvider,
            $this->configManager,
            $this->entityProvider,
            $this->translator,
            $searchAclHelper,
            $eventDispatcher
        );
    }

    public function testSelect()
    {
        $query = $this->indexService->select();

        $this->assertAttributeEquals($this->entityManager, 'em', $query);
        $this->assertEquals($this->config, $query->getMappingConfig());
        $this->assertEquals('select', $query->getQuery());
    }

    public function testQuery()
    {
        $select = $this->indexService->select();

        $resultItem = new Item($this->entityManager);
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
            'no extra parameters'             => [
                'expectedQuery' => 'select from * where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
            ],
            'custom offset'                   => [
                'expectedQuery' => 'select from * where text all_text ~ "qwerty" offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
            ],
            'custom offset custom maxResults' => [
                'expectedQuery' => 'select from * where text all_text ~ "qwerty" limit 200 offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
            ],
            'custom from'                     => [
                'expectedQuery' => 'select from test_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => 0,
                'maxResults'    => 0,
                'from'          => 'test_customer',
            ],
            'all custom parameters'           => [
                'expectedQuery' => 'select from test_customer where text all_text ~ "qwerty" limit 200 offset 400',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
                'from'          => 'test_customer',
                'page'          => 3,
            ],
            'search by inherited entity'      => [
                'expectedQuery' => 'select from concrete_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'concrete_customer',
            ],
            'search by superclass entity, mode including descendants'     => [
                'expectedQuery' => 'select from customer, concrete_customer where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'customer',
            ],
            'search by abstract entity, mode descendants only'     => [
                'expectedQuery' => 'select from repeatable_task, scheduled_task where text all_text ~ "qwerty"',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'task',
            ],
            'unknown from'                    => [
                'expectedQuery' => 'select from unknown_entity where text all_text ~ "qwerty"',
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
            ' order_by name offset 10 max_results 5';
        $expectedQuery = 'select from test_product where ((integer qty in (2, 5) or decimal price = 10) '
            .'and integer count > 10 and text name ~ "test string") order by name ASC limit 5 offset 10';

        $result = $this->indexService->advancedSearch($sourceQuery);
        $actualQuery = $result->getQuery()->getStringQuery();

        $this->assertEquals($searchResults, $result->getElements());
        $this->assertEquals(count($searchResults), $result->getRecordsCount());
        $this->assertEquals($expectedQuery, $actualQuery);
    }
}
