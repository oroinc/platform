<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\FirstTestBundle;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ConfigProvider */
    private $configurationProvider;

    /** @var string */
    private $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('DachboardConfigurationProvider');
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->configurationProvider = new ConfigProvider($this->cacheFile, false, $this->eventDispatcher);

        $bundle1 = new FirstTestBundle();
        $bundle2 = new SecondTestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testConfiguration()
    {
        $expectedConfiguration = [
            'widgets'    => [
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
            ],
            'dashboards' => [
                'main'                  => [
                    'twig' => 'OroDashboardBundle:Index:default.html.twig'
                ],
                'alternative_dashboard' => [
                    'twig' => 'OroDashboardBundle:Index:default.html.twig'
                ],
                'empty_board'           => [
                    'twig' => 'OroDashboardBundle:Index:default.html.twig'
                ]
            ]
        ];

        self::assertEquals(
            $expectedConfiguration['widgets'],
            $this->configurationProvider->getWidgetConfigs()
        );
        foreach ($expectedConfiguration['widgets'] as $name => $config) {
            self::assertTrue(
                $this->configurationProvider->hasWidgetConfig($name),
                $name
            );
            self::assertEquals(
                $config,
                $this->configurationProvider->getWidgetConfig($name),
                $name
            );
        }

        self::assertEquals(
            $expectedConfiguration['dashboards'],
            $this->configurationProvider->getDashboardConfigs()
        );
        foreach ($expectedConfiguration['dashboards'] as $name => $config) {
            self::assertTrue(
                $this->configurationProvider->hasDashboardConfig($name),
                $name
            );
            self::assertEquals(
                $config,
                $this->configurationProvider->getDashboardConfig($name),
                $name
            );
        }
    }

    public function testHasDashboardConfigForUnknownDashboard()
    {
        $this->assertFalse($this->configurationProvider->hasDashboardConfig('unknown'));
    }

    public function testGetWidgetConfigForUnknownWidget()
    {
        $this->expectException(\Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage("Can't find configuration for: unknown");

        $this->configurationProvider->getWidgetConfig('unknown');
    }

    public function testHasWidgetConfigForUnknownWidget()
    {
        $this->assertFalse($this->configurationProvider->hasWidgetConfig('unknown'));
    }

    public function testGetDashboardConfigForUnknownDashboard()
    {
        $this->expectException(\Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage("Can't find configuration for: unknown");

        $this->configurationProvider->getDashboardConfig('unknown');
    }
}
