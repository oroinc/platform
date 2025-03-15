<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Component\Config\Resolver\ResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetConfigsTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private ResolverInterface&MockObject $resolver;
    private ManagerRegistry&MockObject $doctrine;
    private ConfigValueProvider&MockObject $valueProvider;
    private TranslatorInterface&MockObject $translator;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private WidgetConfigs $widgetConfigs;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->resolver = $this->createMock(ResolverInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
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

        $this->widgetConfigs = new WidgetConfigs(
            $this->configProvider,
            $this->resolver,
            $this->doctrine,
            $this->valueProvider,
            $this->translator,
            $this->eventDispatcher,
            $widgetConfigVisibilityFilter,
            new RequestStack()
        );
    }

    public function testGetWidgetAttributesForTwig(): void
    {
        $expectedWidgetName = 'widget_name';
        $configs = [
            'route' => 'sample route',
            'route_parameters' => 'sample params',
            'acl' => 'view_acl',
            'items' => [],
            'test-param' => 'param',
            'configuration' => []
        ];
        $expected = [
            'widgetName' => $expectedWidgetName,
            'widgetTestParam' => 'param',
            'widgetConfiguration' => []
        ];
        $this->configProvider->expects(self::once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->willReturn($configs);

        $actual = $this->widgetConfigs->getWidgetAttributesForTwig($expectedWidgetName);
        self::assertEquals($expected, $actual);
    }

    public function testGetWidgetItems(): void
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
            'label' => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled' => true
        ];
        $notApplicableItem = 'not_applicable_item';
        $notApplicable = [
            'label' => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled' => true
        ];
        $disabledItem = 'not_applicable_item';
        $disabled = [
            'label' => 'applicable is set and resolved to false',
            'acl' => $allowedAcl,
            'enabled' => false
        ];

        $configs = [
            $expectedItem => $expectedValue,
            $notGrantedItem => $notGrantedValue,
            $applicableItem => $applicable,
            $notApplicableItem => $notApplicable,
            $disabledItem => $disabled
        ];

        $this->configProvider->expects(self::once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->willReturn(['items' => $configs]);

        $result = $this->widgetConfigs->getWidgetItems($expectedWidgetName);
        self::assertArrayHasKey($applicableItem, $result);
        self::assertArrayHasKey($expectedItem, $result);
    }

    public function testGetWidgetConfigs(): void
    {
        $notAllowedAcl = 'invalid_acl';
        $allowedAcl = 'valid_acl';
        $expectedItem = 'expected_item';
        $expectedValue = ['label' => 'test label', 'acl' => $allowedAcl, 'enabled' => true];
        $notGrantedItem = 'not_granted_item';
        $notGrantedValue = ['label' => 'not granted label', 'acl' => $notAllowedAcl, 'enabled' => true];
        $applicableItem = 'applicable_item';
        $applicable = [
            'label' => 'applicable is set and resolved to true',
            'applicable' => '@true',
            'enabled' => true
        ];
        $notApplicableItem = 'not_applicable_item';
        $notApplicable = [
            'label' => 'applicable is set and resolved to false',
            'applicable' => '@false',
            'enabled' => true
        ];
        $configs = [
            $expectedItem => $expectedValue,
            $notGrantedItem => $notGrantedValue,
            $applicableItem => $applicable,
            $notApplicableItem => $notApplicable
        ];

        $this->configProvider->expects(self::once())
            ->method('getWidgetConfigs')
            ->willReturn($configs);

        $result = $this->widgetConfigs->getWidgetConfigs();
        self::assertArrayHasKey($applicableItem, $result);
        self::assertArrayHasKey($expectedItem, $result);
    }
}
