<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

use Symfony\Component\HttpFoundation\Request;

class WidgetConfigsTest extends \PHPUnit_Framework_TestCase
{
    private $widgetRepository;

    private $em;
    private $stateManager;

    private $widgetConfigs;

    public function setUp()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver = $this->getMock('Oro\Component\Config\Resolver\ResolverInterface');

        $this->em = $this->getMock('Doctrine\ORM\EntityManagerInterface');

        $this->stateManager = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\StateManager')
                ->disableOriginalConstructor()
                ->getMock();


        $this->widgetConfigs = new WidgetConfigs(
            $configProvider,
            $securityFacade,
            $resolver,
            $this->em,
            $this->stateManager
        );

        $this->widgetRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->em
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroDashboardBundle:Widget')
            ->will($this->returnValue($this->widgetRepository));
    }

    public function testGetCurrentWidgetOptionsShouldReturnEmptyArrayIfRequestIsNull()
    {
        $this->widgetConfigs->setRequest(null);

        $this->assertEmpty($this->widgetConfigs->getCurrentWidgetOptions()->all());
    }

    public function testGetCurrentWidgetOptionsShouldReturnEmptyArrayIfThereIsNoWidgetIdInRequestQuery()
    {
        $request = new Request();
        $this->widgetConfigs->setRequest($request);

        $this->assertEmpty($this->widgetConfigs->getCurrentWidgetOptions()->all());
    }

    public function testGetCurrentWidgetOptionsShouldReturnOptionsOfWidget()
    {
        $request = new Request([
            '_widgetId' => 1,
        ]);
        $this->widgetConfigs->setRequest($request);

        $widget = new Widget();
        $this->widgetRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->will($this->returnValue($widget));

        $options = ['k' => 'v', 'k2' => 'v2'];
        $widgetState = new WidgetState();
        $widgetState->setOptions($options);
        $this->stateManager
            ->expects($this->once())
            ->method('getWidgetState')
            ->with($widget)
            ->will($this->returnValue($widgetState));

        $this->assertEquals(new WidgetOptionBag($options), $this->widgetConfigs->getCurrentWidgetOptions());
    }
}
