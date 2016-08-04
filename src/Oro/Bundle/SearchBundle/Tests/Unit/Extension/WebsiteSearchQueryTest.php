<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Search;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Extension\WebsiteSearchQuery;
use Oro\Bundle\SearchBundle\Query\Query;

class WebsiteSearchQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchQuery
     */
    protected $testable;

    /**
     * @var EngineV2Interface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $engine;

    /**
     * @var Query|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;

    public function setUp()
    {
        $this->engine = $this->getMockBuilder(EngineV2Interface::class)
            ->getMock();

        $this->query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testable = new WebsiteSearchQuery($this->engine, $this->query);
    }

    /**
     * @throws \BadMethodCallException
     */
    public function testQueryShouldRaiseException()
    {
        $this->setExpectedException(\BadMethodCallException::class);
        $this->testable->query();
    }

    public function testExecuteShouldCallEngine()
    {
        $this->engine->expects($this->once())
            ->method('search')
            ->with($this->query);

        $this->testable->execute();
    }
}
