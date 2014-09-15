<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetAttributes;

class WidgetAttributesTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetAttributes */
    protected $target;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resolver;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = $this->getMock('Oro\Component\Config\Resolver\ResolverInterface');

        $this->target = new WidgetAttributes($this->configProvider, $this->securityFacade, $this->resolver);
    }

    public function testGetWidgetAttributesForTwig()
    {
        $expectedWidgetName = 'widget_name';
        $configs            = [
            'route'            => 'sample route',
            'route_parameters' => 'sample params',
            'acl'              => 'view_acl',
            'items'            => [],
            'test-param'       => 'param'
        ];
        $expected           = ['widgetName' => $expectedWidgetName, 'widgetTestParam' => 'param'];
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
        $notAllowedAcl      = 'invalid_acl';
        $allowedAcl         = 'valid_acl';
        $expectedItem       = 'expected_item';
        $expectedValue      = ['label' => 'test label', 'acl' => $allowedAcl];
        $notGrantedItem     = 'not_granted_item';
        $notGrantedValue    = ['label' => 'not granted label', 'acl' => $notAllowedAcl];
        $applicableItem     = 'applicable_item';
        $applicable         = ['label' => 'applicable is set and resolved to true', 'applicable' => '@true'];
        $notApplicableItem  = 'not_applicable_item';
        $notApplicable      = ['label' => 'applicable is set and resolved to false', 'applicable' => '@false'];
        $configs            = [
            $expectedItem      => $expectedValue,
            $notGrantedItem    => $notGrantedValue,
            $applicableItem    => $applicable,
            $notApplicableItem => $notApplicable
        ];

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue(['items' => $configs]));

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true'], [], true],
                        [$allowedAcl, null, true]
                    ]
                )
            );
        $this->resolver->expects($this->exactly(2))
            ->method('resolve')
            ->will(
                $this->returnValueMap(
                    [

                        [['@false'], [], [false]],
                        [['@true'], [], [true]],
                    ]
                )
            );

        $result = $this->target->getWidgetItems($expectedWidgetName);
        $this->assertArrayHasKey($applicableItem, $result);
        $this->assertArrayHasKey($expectedItem, $result);
    }
}
