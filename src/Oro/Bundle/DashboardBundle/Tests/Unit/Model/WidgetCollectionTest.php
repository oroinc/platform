<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\Factory;
use Oro\Bundle\DashboardBundle\Model\WidgetCollection;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;

class WidgetCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Dashboard|\PHPUnit\Framework\MockObject\MockObject */
    private $dashboard;

    /** @var Factory|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var WidgetCollection */
    private $collection;

    protected function setUp(): void
    {
        $this->dashboard = $this->createMock(Dashboard::class);
        $this->factory = $this->createMock(Factory::class);

        $this->collection = new WidgetCollection($this->dashboard, $this->factory);
    }

    public function testInitialize()
    {
        $fooWidget = $this->createMock(Widget::class);
        $fooWidgetModel = $this->createMock(WidgetModel::class);

        $barWidget = $this->createMock(Widget::class);
        $barWidgetModel = $this->createMock(WidgetModel::class);

        $this->dashboard->expects($this->once())
            ->method('getWidgets')
            ->willReturn([$fooWidget, $barWidget]);

        $this->factory->expects($this->exactly(2))
            ->method('createVisibleWidgetModel')
            ->willReturnMap([
                [$fooWidget, $fooWidgetModel],
                [$barWidget, $barWidgetModel],
            ]);

        $this->assertEquals(
            [$fooWidgetModel, $barWidgetModel],
            $this->collection->toArray()
        );
    }
}
