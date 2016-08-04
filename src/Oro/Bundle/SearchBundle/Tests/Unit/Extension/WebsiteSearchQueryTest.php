<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Search;

use Oro\Bundle\SearchBundle\Extension\WebsiteSearchQuery;
use Symfony\Component\Intl\Exception\MethodNotImplementedException;

class WebsiteSearchQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchQuery
     */
    protected $testable;

    public function setUp()
    {
        $this->testable = $this->getMockBuilder(WebsiteSearchQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @throws MethodNotImplementedException
     */
    public function testQueryShouldRaiseException()
    {
        $this->testable->query();
    }
}
