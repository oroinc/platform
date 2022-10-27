<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilterInterface;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilterManager;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class WidgetProviderFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilter()
    {
        $filter = $this->createMock(WidgetProviderFilterInterface::class);
        $filter->expects($this->once())
            ->method('filter')
            ->willReturnCallback(function (QueryBuilder $queryBuilder) {
                $queryBuilder->addSelect();
            });

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('addSelect');

        $manager = new WidgetProviderFilterManager([$filter]);
        $manager->filter($qb, new WidgetOptionBag());
    }
}
