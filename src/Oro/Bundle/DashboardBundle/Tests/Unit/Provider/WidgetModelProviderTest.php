<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider;

use Oro\Bundle\DashboardBundle\Provider\WidgetModelProvider;

class WidgetModelProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WidgetModelProvider
     */
    protected $widgetModelProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetModelFactory;

    protected function setUp()
    {
        $this->widgetRepository = $this->getMockBuilder(
            'Oro\Bundle\DashboardBundle\Entity\Repository\DashboardWidgetRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetModelFactory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModelFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetModelProvider = new WidgetModelProvider($this->widgetRepository, $this->widgetModelFactory);
    }

    public function testGetAvailableWidgets()
    {

        $firstWidget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\DashboardWidget');
        $secondWidget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\DashboardWidget');
        $this->widgetRepository->expects($this->once())
            ->method('getAvailableWidgets')
            ->will($this->returnValue(array($firstWidget, $secondWidget)));

        $this->widgetModelFactory->expects($this->exactly(2))->method('getModel');

        $result = $this->widgetModelProvider->getAvailableWidgets();

        $this->assertCount(2, $result);
    }
}
