<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Configuration\OperationConfigurationValidator;
use Oro\Bundle\ActionBundle\Configuration\OperationListConfiguration;
use Oro\Bundle\CacheBundle\Loader\ConfigurationLoader;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    const ROOT_NODE_NAME = 'test_root_node';
    const ROOT_NODE_OPERATION = 'operations';

    const BUNDLE1 = 'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1';
    const BUNDLE2 = 'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2';
    const BUNDLE3 = 'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle3\TestBundle3';

    /** @var \PHPUnit\Framework\MockObject\MockObject|OperationListConfiguration */
    protected $definitionConfiguration;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OperationConfigurationValidator */
    protected $definitionConfigurationValidator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheProvider */
    protected $cacheProvider;

    protected function setUp()
    {
        $this->definitionConfiguration = $this->createMock(OperationListConfiguration::class);
        $this->definitionConfigurationValidator = $this->createMock(OperationConfigurationValidator::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);
    }

    public function testGetActionConfigurationWithCache()
    {
        $config = ['test' => 'config'];

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(self::ROOT_NODE_NAME)
            ->willReturn($config);

        $configurationProvider = new ConfigurationProvider(
            new ConfigurationLoader(),
            $this->definitionConfiguration,
            $this->definitionConfigurationValidator,
            $this->cacheProvider,
            [],
            [],
            self::ROOT_NODE_NAME
        );

        $this->assertEquals($config, $configurationProvider->getConfiguration());
    }

    public function testWarmUpCache()
    {
        $this->assertConfigurationCacheBuilt();

        $configurationProvider = new ConfigurationProvider(
            new ConfigurationLoader(),
            $this->definitionConfiguration,
            $this->definitionConfigurationValidator,
            $this->cacheProvider,
            [self::BUNDLE1 => ['test_action' => ['label' => 'Test Action']]],
            [self::BUNDLE1],
            self::ROOT_NODE_NAME
        );

        $configurationProvider->warmUpCache();
    }

    public function testWarmUpResourceCache()
    {
        $bundles = [
            'TestBundle1' => self::BUNDLE1,
            'TestBundle2' => self::BUNDLE2,
        ];

        $temporaryContainer = new ContainerBuilder();
        CumulativeResourceManager::getInstance()->clear()->setBundles($bundles);

        $this->definitionConfiguration->expects($this->once())
            ->method('processConfiguration')
            ->willReturnArgument(0);

        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with(
                self::ROOT_NODE_OPERATION,
                [
                    'test_operation1' => ['label' => 'Test Operation 1'],
                    'test_operation2' => ['label' => 'Test Operation 2'],
                    'test_operation4' => ['label' => 'Test Operation 4']
                ]
            );

        $configurationProvider = new ConfigurationProvider(
            new ConfigurationLoader(),
            $this->definitionConfiguration,
            $this->definitionConfigurationValidator,
            $this->cacheProvider,
            [],
            $bundles,
            self::ROOT_NODE_OPERATION
        );

        $configurationProvider->warmUpResourceCache($temporaryContainer);

        $resources = $temporaryContainer->getResources();
        $this->assertCount(1, $resources);
    }

    public function testClearCache()
    {
        $configurationProvider = new ConfigurationProvider(
            new ConfigurationLoader(),
            $this->definitionConfiguration,
            $this->definitionConfigurationValidator,
            $this->cacheProvider,
            [],
            [],
            self::ROOT_NODE_NAME
        );

        $this->cacheProvider->expects($this->once())
            ->method('delete')
            ->with(self::ROOT_NODE_NAME);

        $configurationProvider->clearCache();
    }

    public function testGetActionConfigurationWithIgnoreCache()
    {
        $config = [
            'action1' => [
                'label' => 'Label1',
            ],
        ];

        $this->cacheProvider->expects($this->never())->method('fetch');
        $this->cacheProvider->expects($this->never())->method('save');

        $this->definitionConfiguration->expects($this->once())
            ->method('processConfiguration')
            ->willReturnCallback(function ($config) {
                return $config;
            });

        $this->definitionConfigurationValidator->expects($this->once())
            ->method('validate')
            ->with($config);

        $configurationProvider = new ConfigurationProvider(
            new ConfigurationLoader(),
            $this->definitionConfiguration,
            $this->definitionConfigurationValidator,
            $this->cacheProvider,
            [self::BUNDLE1 => $config],
            [self::BUNDLE1],
            self::ROOT_NODE_NAME
        );

        $this->assertEquals($config, $configurationProvider->getConfiguration(true));
    }

    /**
     * @dataProvider getActionConfigurationDataProvider
     *
     * @param array $rawConfig
     * @param array $expected
     */
    public function testGetActionConfigurationWithoutCache(array $rawConfig, array $expected)
    {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(self::ROOT_NODE_NAME)
            ->willReturn(false);

        $this->assertConfigurationCacheBuilt();

        $configurationProvider = new ConfigurationProvider(
            new ConfigurationLoader(),
            $this->definitionConfiguration,
            $this->definitionConfigurationValidator,
            $this->cacheProvider,
            $rawConfig,
            [self::BUNDLE1, self::BUNDLE2, self::BUNDLE3],
            self::ROOT_NODE_NAME
        );

        $configs = $configurationProvider->getConfiguration();

        $this->assertInternalType('array', $configs);
        $this->assertEquals($expected, $configs);
    }

    /**
     * @return array
     */
    public function getActionConfigurationDataProvider()
    {
        return [
            [
                [
                    self::BUNDLE1 => [
                        'test_action1' => [
                            'label' => 'Test Action1',
                            'replace' => ['test'],
                            'routes' => ['test_route_bundle1']
                        ],
                        'test_action2' => [
                            'extends' => 'test_action1'
                        ],
                        'test_action4' => [
                            'label' => 'Test Action1',
                            'some_config' => [
                                'sub_config1' => 'data1',
                                'sub_config2' => 'data2',
                                'sub_config3' => 'data3',
                            ],
                            'message' => 'custom value with %%percent escaped string%% parameter'
                        ]
                    ],
                    self::BUNDLE2 => [
                        'test_action1' => [
                            'replace' => ['routes'],
                        ],
                        'test_action4' => [
                            'label' => 'Test Action4',
                            'some_config' => [
                                'replace' => ['sub_config1', 'sub_config3'],
                                'sub_config3' => 'replaced data',
                            ],
                            'custom key with %%percent escaped string%% parameter' => 'value'
                        ]
                    ],
                    self::BUNDLE3 => [
                        'test_action1' => [
                            'replace' => ['routes'],
                            'routes' => ['test_route_bundle3']
                        ],
                        'test_action2' => [
                            'label' => 'Test Action2 Bundle3',
                            'extends' => 'test_action1',
                        ],
                        'test_action3' => [
                            'extends' => 'test_action2',
                            'routes' => ['test_route_bundle3_new']
                        ]
                    ]
                ],
                [
                    'test_action1' => [
                        'label' => 'Test Action1',
                        'routes' => ['test_route_bundle3']
                    ],
                    'test_action2' => [
                        'label' => 'Test Action2 Bundle3',
                        'routes' => ['test_route_bundle3']
                    ],
                    'test_action4' => [
                        'label' => 'Test Action4',
                        'some_config' => ['sub_config2' => 'data2', 'sub_config3' => 'replaced data'],
                        'message' => 'custom value with %percent escaped string% parameter',
                        'custom key with %percent escaped string% parameter' => 'value'
                    ],
                    'test_action3' => [
                        'label' => 'Test Action2 Bundle3',
                        'routes' => ['test_route_bundle3', 'test_route_bundle3_new']
                    ]
                ]
            ]
        ];
    }

    protected function assertConfigurationCacheBuilt()
    {
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with(self::ROOT_NODE_NAME)
            ->willReturn(true);

        $this->definitionConfiguration->expects($this->once())
            ->method('processConfiguration')
            ->willReturnCallback(function (array $configs) {
                return $configs;
            });

        $this->definitionConfigurationValidator->expects($this->once())->method('validate');
    }
}
