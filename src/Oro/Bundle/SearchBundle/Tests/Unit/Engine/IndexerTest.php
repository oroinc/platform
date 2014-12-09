<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class IndexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Indexer
     */
    protected $indexService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mapper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $engine;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityProvider;

    /**
     * @var array
     */
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

        $this->securityProvider = $this->getMockBuilder('Oro\Bundle\SearchBundle\Security\SecurityProvider')
            ->disableOriginalConstructor()->getMock();
        $this->securityProvider->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));
        $this->securityProvider->expects($this->any())
            ->method('isProtectedEntity')
            ->will($this->returnValue(true));

        $this->indexService = new Indexer(
            $this->entityManager,
            $this->engine,
            $this->mapper,
            $this->securityProvider
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
        $searchResults = array($resultItem);

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
        return array(
            'no extra parameters'             => array(
                'expectedQuery' => 'select from * where and((text)all_text ~ qwerty)',
                'string'        => 'qwerty',
            ),
            'custom offset'                   => array(
                'expectedQuery' => 'select from * where and((text)all_text ~ qwerty) offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
            ),
            'custom offset custom maxResults' => array(
                'expectedQuery' => 'select from * where and((text)all_text ~ qwerty) limit 200 offset 10',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
            ),
            'custom from'                     => array(
                'expectedQuery' => 'select from test_customer where and((text)all_text ~ qwerty)',
                'string'        => 'qwerty',
                'offset'        => 0,
                'maxResults'    => 0,
                'from'          => 'test_customer',
            ),
            'all custom parameters'           => array(
                'expectedQuery' => 'select from test_customer where and((text)all_text ~ qwerty) limit 200 offset 400',
                'string'        => 'qwerty',
                'offset'        => 10,
                'maxResults'    => 200,
                'from'          => 'test_customer',
                'page'          => 3,
            ),
            'search by inherited entity'      => array(
                'expectedQuery' => 'select from concrete_customer where and((text)all_text ~ qwerty)',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'concrete_customer',
            ),
            'search by superclass entity, mode including descendants'     => array(
                'expectedQuery' => 'select from customer, concrete_customer where and((text)all_text ~ qwerty)',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'customer',
            ),
            'search by abstract entity, mode descendants only'     => array(
                'expectedQuery' => 'select from repeatable_task, scheduled_task where and((text)all_text ~ qwerty)',
                'string'        => 'qwerty',
                'offset'        => null,
                'maxResults'    => null,
                'from'          => 'task',
            ),
            'unknown from'                    => array(
                'expectedQuery' => 'select where and((text)all_text ~ qwerty)',
                'string'        => 'qwerty',
                'offset'        => 0,
                'maxResults'    => 0,
                'from'          => 'unknown_entity',
            ),
        );
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
        $searchResults = array('one', 'two', 'three');

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
        $actualQuery = $this->combineQueryString($result->getQuery());

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
        $searchResults = array('one', 'two', 'three');

        $this->engine->expects($this->any())
            ->method('search')
            ->will(
                $this->returnCallback(
                    function (Query $query) use ($searchResults) {
                        return new Result($query, $searchResults, count($searchResults));
                    }
                )
            );

        $sourceQuery = 'from (test_product, test_category)' .
            ' where name ~ "test string" and integer count = 10 and decimal price in (10, 12, 15)' .
            ' order_by name offset 10 max_results 5';
        $expectedQuery = 'select from test_product' .
            ' where and((text)name ~ test string) and((integer)count = 10) and((decimal)price in (10, 12, 15))' .
            ' order by name asc limit 5 offset 10';

        $result = $this->indexService->advancedSearch($sourceQuery);
        $actualQuery = $this->combineQueryString($result->getQuery());

        $this->assertEquals($searchResults, $result->getElements());
        $this->assertEquals(count($searchResults), $result->getRecordsCount());
        $this->assertEquals($expectedQuery, $actualQuery);
    }

    /**
     * @param Query $query
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function combineQueryString(Query $query)
    {
        $selectString = $query->getQuery();

        $fromString = '';
        if ($query->getFrom()) {
            $fromString .=  ' from ' . implode(', ', $query->getFrom());
        }

        $whereParts = array();
        foreach ($query->getOptions() as $whereOptions) {
            if (is_array($whereOptions['fieldValue'])) {
                $whereOptions['fieldValue'] = '(' . implode(', ', $whereOptions['fieldValue']) . ')';
            }
            $whereParts[] = sprintf(
                '%s((%s)%s %s %s)',
                $whereOptions['type'],
                $whereOptions['fieldType'],
                $whereOptions['fieldName'],
                $whereOptions['condition'],
                $whereOptions['fieldValue']
            );
        }
        $whereString = '';
        if ($whereParts) {
            $whereString .= ' where ' . implode(' ', $whereParts);
        }

        $orderByString = '';
        if ($query->getOrderBy()) {
            $orderByString .= ' ' . $query->getOrderBy();
        }
        if ($query->getOrderDirection()) {
            $orderByString .= ' ' . $query->getOrderDirection();
        }
        if ($orderByString) {
            $orderByString = ' order by' . $orderByString;
        }

        $limitString = '';
        if ($query->getMaxResults() && $query->getMaxResults() != Query::INFINITY) {
            $limitString = ' limit ' . $query->getMaxResults();
        }

        $offsetString = '';
        if ($query->getFirstResult()) {
            $offsetString .= ' offset ' . $query->getFirstResult();
        }

        return $selectString . $fromString. $whereString . $orderByString . $limitString . $offsetString;
    }
}
