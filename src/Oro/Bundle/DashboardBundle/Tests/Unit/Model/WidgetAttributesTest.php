<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Symfony\Component\HttpFoundation\RequestStack;

class WidgetAttributesTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetConfigs */
    protected $target;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $resolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $valueProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $widgetConfigVisibilityFilter;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = $this->createMock('Oro\Component\Config\Resolver\ResolverInterface');

        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');

        $this->valueProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $widgetConfigVisibilityFilter = $this
            ->getMockBuilder('Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $widgetConfigVisibilityFilter->expects($this->any())
            ->method('filterConfigs')
            ->will($this->returnCallback(function (array $configs) {
                return array_filter(
                    $configs,
                    function ($config) {
                        return (!isset($config['acl']) || $config['acl'] === 'valid_acl') &&
                            (!isset($config['enabled']) || $config['enabled']) &&
                            (!isset($config['applicable']) || $config['applicable'] === '@true');
                    }
                );
            }));

        $this->target = new WidgetConfigs(
            $this->configProvider,
            $this->resolver,
            $em,
            $this->valueProvider,
            $this->translator,
            $this->eventDispatcher,
            $widgetConfigVisibilityFilter,
            new RequestStack()
        );
    }

    public function testGetWidgetAttributesForTwig()
    {
        $expectedWidgetName = 'widget_name';
        $configs            = [
            'route'            => 'sample route',
            'route_parameters' => 'sample params',
            'acl'              => 'view_acl',
            'items'            => [],
            'test-param'       => 'param',
            'configuration'    => []
        ];
        $expected           = [
            'widgetName'          => $expectedWidgetName,
            'widgetTestParam'     => 'param',
            'widgetConfiguration' => []
        ];
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
        $expectedValue      = ['label' => 'test label', 'acl' => $allowedAcl, 'enabled' => true];
        $notGrantedItem     = 'not_granted_item';
        $notGrantedValue    = ['label' => 'not granted label', 'acl' => $notAllowedAcl, 'enabled' => true];
        $applicableItem     = 'applicable_item';
        $applicable         = [
            'label'      => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled'    => true
        ];
        $notApplicableItem  = 'not_applicable_item';
        $notApplicable      = [
            'label'      => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled'    => true
        ];
        $disabledItem       = 'not_applicable_item';
        $disabled           = [
            'label'   => 'applicable is set and resolved to false',
            'acl'     => $allowedAcl,
            'enabled' => false
        ];

        $configs = [
            $expectedItem      => $expectedValue,
            $notGrantedItem    => $notGrantedValue,
            $applicableItem    => $applicable,
            $notApplicableItem => $notApplicable,
            $disabledItem      => $disabled
        ];

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue(['items' => $configs]));

        $result = $this->target->getWidgetItems($expectedWidgetName);
        $this->assertArrayHasKey($applicableItem, $result);
        $this->assertArrayHasKey($expectedItem, $result);
    }

    public function testGetWidgetConfigs()
    {
        $notAllowedAcl     = 'invalid_acl';
        $allowedAcl        = 'valid_acl';
        $expectedItem      = 'expected_item';
        $expectedValue     = ['label' => 'test label', 'acl' => $allowedAcl, 'enabled' => true];
        $notGrantedItem    = 'not_granted_item';
        $notGrantedValue   = ['label' => 'not granted label', 'acl' => $notAllowedAcl, 'enabled' => true];
        $applicableItem    = 'applicable_item';
        $applicable        = [
            'label'      => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled'    => true
        ];
        $notApplicableItem = 'not_applicable_item';
        $notApplicable     = [
            'label'      => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled'    => true
        ];
        $configs           = [
            $expectedItem      => $expectedValue,
            $notGrantedItem    => $notGrantedValue,
            $applicableItem    => $applicable,
            $notApplicableItem => $notApplicable
        ];

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfigs')
            ->will($this->returnValue($configs));

        $result = $this->target->getWidgetConfigs();
        $this->assertArrayHasKey($applicableItem, $result);
        $this->assertArrayHasKey($expectedItem, $result);
    }
}
