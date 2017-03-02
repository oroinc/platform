<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Filter;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilterManager;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity\TestFilter;

class WidgetProviderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetProviderFilterManager */
    protected $widgetProviderFilter;

    public function setUp()
    {
        $this->widgetProviderFilter = new WidgetProviderFilterManager();
        $this->widgetProviderFilter->addFilter(new TestFilter());
    }

    public function testFilter()
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('addSelect');

        $this->widgetProviderFilter->filter($qb, new WidgetOptionBag());
    }
}
