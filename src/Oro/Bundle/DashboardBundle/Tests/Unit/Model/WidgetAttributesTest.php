<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetAttributesTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $resolver;

    /** @var ConfigValueProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $valueProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var WidgetConfigs */
    private $target;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->resolver = $this->createMock(ResolverInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->valueProvider = $this->createMock(ConfigValueProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $widgetConfigVisibilityFilter = $this->createMock(WidgetConfigVisibilityFilter::class);
        $widgetConfigVisibilityFilter->expects($this->any())
            ->method('filterConfigs')
            ->willReturnCallback(function (array $configs) {
                return array_filter(
                    $configs,
                    function ($config) {
                        return (!isset($config['acl']) || $config['acl'] === 'valid_acl') &&
                            (!isset($config['enabled']) || $config['enabled']) &&
                            (!isset($config['applicable']) || $config['applicable'] === '@true');
                    }
                );
            });

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
        $configs = [
            'route'            => 'sample route',
            'route_parameters' => 'sample params',
            'acl'              => 'view_acl',
            'items'            => [],
            'test-param'       => 'param',
            'configuration'    => []
        ];
        $expected = [
            'widgetName'          => $expectedWidgetName,
            'widgetTestParam'     => 'param',
            'widgetConfiguration' => []
        ];
        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->willReturn($configs);

        $actual = $this->target->getWidgetAttributesForTwig($expectedWidgetName);
        $this->assertEquals($expected, $actual);
    }

    public function testGetWidgetItems()
    {
        $expectedWidgetName = 'widget_name';
        $notAllowedAcl = 'invalid_acl';
        $allowedAcl = 'valid_acl';
        $expectedItem = 'expected_item';
        $expectedValue = ['label' => 'test label', 'acl' => $allowedAcl, 'enabled' => true];
        $notGrantedItem = 'not_granted_item';
        $notGrantedValue = ['label' => 'not granted label', 'acl' => $notAllowedAcl, 'enabled' => true];
        $applicableItem = 'applicable_item';
        $applicable = [
            'label'      => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled'    => true
        ];
        $notApplicableItem  = 'not_applicable_item';
        $notApplicable = [
            'label'      => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled'    => true
        ];
        $disabledItem = 'not_applicable_item';
        $disabled = [
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
            ->willReturn(['items' => $configs]);

        $result = $this->target->getWidgetItems($expectedWidgetName);
        $this->assertArrayHasKey($applicableItem, $result);
        $this->assertArrayHasKey($expectedItem, $result);
    }

    public function testGetWidgetConfigs()
    {
        $notAllowedAcl = 'invalid_acl';
        $allowedAcl = 'valid_acl';
        $expectedItem = 'expected_item';
        $expectedValue = ['label' => 'test label', 'acl' => $allowedAcl, 'enabled' => true];
        $notGrantedItem = 'not_granted_item';
        $notGrantedValue = ['label' => 'not granted label', 'acl' => $notAllowedAcl, 'enabled' => true];
        $applicableItem = 'applicable_item';
        $applicable = [
            'label'      => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled'    => true
        ];
        $notApplicableItem = 'not_applicable_item';
        $notApplicable = [
            'label'      => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled'    => true
        ];
        $configs = [
            $expectedItem      => $expectedValue,
            $notGrantedItem    => $notGrantedValue,
            $applicableItem    => $applicable,
            $notApplicableItem => $notApplicable
        ];

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfigs')
            ->willReturn($configs);

        $result = $this->target->getWidgetConfigs();
        $this->assertArrayHasKey($applicableItem, $result);
        $this->assertArrayHasKey($expectedItem, $result);
    }
}
