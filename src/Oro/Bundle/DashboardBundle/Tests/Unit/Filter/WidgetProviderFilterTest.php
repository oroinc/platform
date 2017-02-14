<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Filter;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilter;

class WidgetProviderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var OwnerHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $ownerHelper;

    /** @var WidgetProviderFilter */
    protected $widgetProviderFilter;

    public function setUp()
    {
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownerHelper = $this->getMockBuilder('Oro\Bundle\UserBundle\Dashboard\OwnerHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetProviderFilter = new WidgetProviderFilter($this->aclHelper, $this->ownerHelper);
    }

    public function testFilter()
    {
        $this->ownerHelper->expects($this->once())
            ->method('getOwnerIds')
            ->willReturn([]);
        $this->aclHelper->expects($this->once())
            ->method('apply');

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('getRootAliases')
            ->willReturn(['o']);

        $this->widgetProviderFilter->filter($qb, new WidgetOptionBag());
    }
}
