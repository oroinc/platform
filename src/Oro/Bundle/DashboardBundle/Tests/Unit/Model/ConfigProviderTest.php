<?php
declare(strict_types=1);

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\FirstTestBundle;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private ConfigProvider $configurationProvider;
    private array $expectedDashboardConfigs;
    private array $expectedWidgetConfigs;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('DashboardConfigurationProvider');
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->configurationProvider = new ConfigProvider($cacheFile, false, $eventDispatcher);

        $bundle1 = new FirstTestBundle();
        $bundle2 = new SecondTestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->expectedDashboardConfigs = [
            'main'                  => ['twig' => '@OroDashboard/Index/default.html.twig'],
            'alternative_dashboard' => ['twig' => '@OroDashboard/Index/default.html.twig'],
            'empty_board'           => ['twig' => '@OroDashboard/Index/default.html.twig']
        ];

        $this->expectedWidgetConfigs = [
            'quick_launchpad'        => [
                'route'                        => 'alternative_quick_lanchpad_route',
                'route_parameters'             => [
                    'bundle' => 'TestBundle',
                    'name'   => 'quickLaunchpad',
                    'widget' => 'quick_launchpad'
                ],
                'items'                        => [
                    'test1'  => [
                        'label'            => 'Test1',
                        'route'            => 'test1',
                        'route_parameters' => [],
                        'enabled'          => true
                    ],
                    'index'  => [
                        'label'            => 'List',
                        'route'            => 'oro_sales_opportunity_index',
                        'acl'              => 'oro_sales_opportunity_view',
                        'route_parameters' => [],
                        'enabled'          => true
                    ],
                    'create' => [
                        'label'            => 'Create opportunity',
                        'route'            => 'oro_sales_opportunity_create',
                        'acl'              => 'oro_sales_opportunity_create',
                        'route_parameters' => [],
                        'enabled'          => true
                    ],
                    'test2'  => [
                        'label'            => 'Test2',
                        'route'            => 'test2',
                        'route_parameters' => [],
                        'enabled'          => true
                    ]
                ],
                'enabled'                      => true,
                'isNew'                        => false,
                'configuration_dialog_options' => ['resizable' => false],
                'configuration'                => [],
                'data_items'                   => [],
            ],
            'second_quick_launchpad' => [
                'route'                        => 'second_quick_launchpad_test_route',
                'route_parameters'             => [
                    'bundle' => 'SecondTestBundle',
                    'name'   => 'secondQuickLaunchpad',
                    'widget' => 'second_quick_launchpad'
                ],
                'isNew'                        => true,
                'enabled'                      => true,
                'configuration_dialog_options' => ['resizable' => false],
                'configuration'                => [],
                'data_items'                   => [],
            ]
        ];
    }

    public function testGetDashboardConfigs()
    {
        self::assertEquals($this->expectedDashboardConfigs, $this->configurationProvider->getDashboardConfigs());
    }

    public function testHasDashboardConfig()
    {
        foreach (\array_keys($this->expectedDashboardConfigs) as $name) {
            self::assertTrue($this->configurationProvider->hasDashboardConfig($name), $name);
        }
    }

    public function testGetDashboardConfig()
    {
        foreach ($this->expectedDashboardConfigs as $name => $config) {
            self::assertEquals($config, $this->configurationProvider->getDashboardConfig($name), $name);
        }
    }

    public function testGetWidgetConfigs()
    {
        self::assertEquals($this->expectedWidgetConfigs, $this->configurationProvider->getWidgetConfigs());
    }

    public function testHasWidgetConfig()
    {
        foreach (\array_keys($this->expectedWidgetConfigs) as $name) {
            self::assertTrue($this->configurationProvider->hasWidgetConfig($name), $name);
        }
    }

    public function testGetWidgetConfig()
    {
        foreach ($this->expectedWidgetConfigs as $name => $config) {
            self::assertEquals($config, $this->configurationProvider->getWidgetConfig($name), $name);
        }
    }

    public function testHasWidgetConfigForUnknownWidget()
    {
        self::assertFalse($this->configurationProvider->hasWidgetConfig('unknown'));
    }

    public function testGetWidgetConfigForUnknownWidget()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Can't find configuration for: unknown");

        $this->configurationProvider->getWidgetConfig('unknown');
    }

    public function testGetWidgetConfigForUnknownWidgetIfExceptioNotAllowed()
    {
        self::assertNull($this->configurationProvider->getWidgetConfig('unknown', false));
    }

    public function testHasDashboardConfigForUnknownDashboard()
    {
        self::assertFalse($this->configurationProvider->hasDashboardConfig('unknown'));
    }

    public function testGetDashboardConfigForUnknownDashboard()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Can't find configuration for: unknown");

        $this->configurationProvider->getDashboardConfig('unknown');
    }
}
