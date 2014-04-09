<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetAttributes;

class WidgetAttributesTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var WidgetAttributes
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new WidgetAttributes($this->configProvider, $this->securityFacade);
    }

    public function testGetWidgetAttributesForTwig()
    {
        $expectedWidgetName = 'widget_name';
        $configs = array(
            'route'            => 'sample route',
            'route_parameters' => 'sample params',
            'acl'              => 'view_acl',
            'items'            => array(),
            'test-param'       => 'param'
        );
        $expected = array('widgetName' => $expectedWidgetName, 'widgetTestParam' => 'param');
        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue($configs));

        $actual = $this->target->getWidgetAttributesForTwig($expectedWidgetName);
        $this->assertEquals($expected, $actual);
    }

    public function testGetWidgetItems()
    {
        $expectedWidgetName = 'widget_name';

        $expectedItem = 'expected_item';
        $expectedValue = array('label' => 'test label', 'acl' => 'valid_acl');
        $notGrantedItem = 'not_granted_item';
        $notGrantedValue = array('label' => 'not granted label', 'acl' => 'invalid_acl');
        $configs = array(
            $expectedItem => $expectedValue,
            $notGrantedItem => $notGrantedValue
        );
        unset($expectedValue['acl']);
        $expected = array($expectedItem => $expectedValue);
        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue(array('items' => $configs)));

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnCallback(
                    function ($parameter) use ($notGrantedValue) {
                        return $notGrantedValue['acl'] != $parameter;
                    }
                )
            );

        $actual = $this->target->getWidgetItems($expectedWidgetName);
        $this->assertEquals($expected, $actual);
    }
}
