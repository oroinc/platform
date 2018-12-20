<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Tests\Unit\Fixtures\TestBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SystemConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var SystemConfigurationPass */
    protected $compiler;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    protected function setUp()
    {
        $this->compiler = new SystemConfigurationPass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess()
    {
        $bundle = new TestBundle();
        $bundles = [$bundle->getName() => get_class($bundle)];
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);
        $this->container->expects($this->once())
            ->method('getExtensions')
            ->will($this->returnValue(['test_bundle' => null]));

        $config = [
            SettingsBuilder::RESOLVED_KEY => true,
            'some_field' => [
                'value' => 'some_val',
                'scope' => 'app',
            ],
            'some_another_field' => [
                'value' => 'some_another_val',
            ],
            'service_another_field' => [
                'value' => null,
            ],
        ];

        $this->container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('test_bundle')
            ->willReturn([[
                'settings' => $config,
            ]]);

        $defaultProviderMock = $this->createDefinitionMock();

        $bagServiceDef = $this->createDefinitionMock();
        $configBagServiceDef = $this->createDefinitionMock();
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn([
                'first_scope_service' => [
                    ['scope' => 'app', 'priority' => 100],
                ],
                'second_scope_service' => [
                    ['scope' => 'user', 'priority' => -100],
                ],
            ]);
        $apiManagerServiceDef = $this->createDefinitionMock();
        $configManagerServiceDef = $this->createDefinitionMock();
        $this->container->expects($this->exactly(4))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [SystemConfigurationPass::CONFIG_DEFINITION_BAG_SERVICE, $bagServiceDef],
                        [SystemConfigurationPass::CONFIG_BAG_SERVICE, $configBagServiceDef],
                        [SystemConfigurationPass::API_MANAGER_SERVICE_ID, $apiManagerServiceDef],
                        [SystemConfigurationPass::MAIN_MANAGER_SERVICE_ID, $configManagerServiceDef],
                        ['oro_config.default_provider', $defaultProviderMock],
                    ]
                )
            );
        $apiManagerServiceDef->expects($this->exactly(2))
            ->method('addMethodCall');

        $bagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), ['test_bundle' => $config]);
        $configBagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), $this->isType('array'))
            ->willReturnCallback(
                function ($index, $argument) {
                    self::assertEquals(
                        ['Test\Class::method'],
                        $argument['groups']['group_with_scalar_configurator_and_handler']['configurator']
                    );
                    self::assertEquals(
                        ['Test\Class::method'],
                        $argument['groups']['group_with_scalar_configurator_and_handler']['handler']
                    );
                    self::assertEquals(
                        ['Test\Class::method'],
                        $argument['groups']['group_with_array_configurator_and_handler']['configurator']
                    );
                    self::assertEquals(
                        ['Test\Class::method'],
                        $argument['groups']['group_with_array_configurator_and_handler']['handler']
                    );
                }
            );
        $this->compiler->process($this->container);
    }

    public function testProcessDefaultValueProvider()
    {
        $bundle = new TestBundle();
        $bundles = [$bundle->getName() => get_class($bundle)];
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);
        $this->container->expects(static::once())
            ->method('getExtensions')
            ->willReturn(['test_bundle' => null]);

        $this->container->expects(static::once())
            ->method('getExtensionConfig')
            ->with('test_bundle')
            ->willReturn([[
                'settings' => [
                    'resolved' => true,
                    'some_field' => [
                        'value' => 'some_val',
                        'scope' => 'app',
                    ],
                    'some_another_field' => [
                        'value' => 'some_another_val',
                    ],
                    'service_another_field' => [
                        'value' => '@oro_config.default_provider',
                    ],
                ],
            ]]);

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_config.default_provider')
            ->willReturn(true);

        $defaultProviderMock = $this->createDefinitionMock();

        $bagServiceDef = $this->createDefinitionMock();
        $configBagServiceDef = $this->createDefinitionMock();
        $this->container->expects(static::once())
            ->method('findTaggedServiceIds')
            ->will(
                $this->returnValue(
                    [
                        'first_scope_service' => [
                            ['scope' => 'app', 'priority' => 100],
                        ],
                        'second_scope_service' => [
                            ['scope' => 'user', 'priority' => -100],
                        ],
                    ]
                )
            );

        $apiManagerServiceDef = $this->createDefinitionMock();
        $configManagerServiceDef = $this->createDefinitionMock();
        $this->container->expects(static::exactly(5))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [SystemConfigurationPass::CONFIG_DEFINITION_BAG_SERVICE, $bagServiceDef],
                        [SystemConfigurationPass::CONFIG_BAG_SERVICE, $configBagServiceDef],
                        [SystemConfigurationPass::API_MANAGER_SERVICE_ID, $apiManagerServiceDef],
                        [SystemConfigurationPass::MAIN_MANAGER_SERVICE_ID, $configManagerServiceDef],
                        ['oro_config.default_provider', $defaultProviderMock],
                    ]
                )
            );

        $bagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), [
                'test_bundle' => [
                    'resolved' => true,
                    'some_field' => [
                        'value' => 'some_val',
                        'scope' => 'app',
                    ],
                    'some_another_field' => [
                        'value' => 'some_another_val',
                    ],
                    'service_another_field' => [
                        'value' => $defaultProviderMock,
                    ],
                ],
            ]);

        $this->compiler->process($this->container);
    }

    /**
     * @return Definition|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createDefinitionMock()
    {
        return $this->createMock(Definition::class);
    }
}
