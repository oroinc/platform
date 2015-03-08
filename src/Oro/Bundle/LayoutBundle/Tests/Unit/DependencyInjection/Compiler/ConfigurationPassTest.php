<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass;

class ConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess()
    {
        $container         = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $factoryBuilderDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $extensionDef      = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $blockTypeServiceIds           = [
            'block1' => [
                ['class' => 'Test\BlockType1', 'alias' => 'test_block_name1']
            ],
            'block2' => [
                ['class' => 'Test\BlockType2', 'alias' => 'test_block_name2']
            ]
        ];
        $blockTypeExtensionServiceIds  = [
            'extension1' => [
                ['class' => 'Test\BlockTypeExtension1', 'alias' => 'test_block_name1']
            ],
            'extension2' => [
                ['class' => 'Test\BlockTypeExtension2', 'alias' => 'test_block_name2']
            ],
            'extension3' => [
                ['class' => 'Test\BlockTypeExtension3', 'alias' => 'test_block_name1', 'priority' => -10]
            ]
        ];
        $layoutUpdateServiceIds        = [
            'update1' => [
                ['class' => 'Test\LayoutUpdate1', 'id' => 'test_block_id1']
            ],
            'update2' => [
                ['class' => 'Test\LayoutUpdate2', 'id' => 'test_block_id2']
            ],
            'update3' => [
                ['class' => 'Test\LayoutUpdate3', 'id' => 'test_block_id1', 'priority' => -10]
            ]
        ];
        $contextConfiguratorServiceIds = [
            'contextConfigurator1' => [
                ['class' => 'Test\ContextConfigurator1']
            ],
            'contextConfigurator2' => [
                ['class' => 'Test\ContextConfigurator2', 'priority' => -10]
            ],
            'contextConfigurator3' => [
                ['class' => 'Test\ContextConfigurator3']
            ]
        ];
        $dataProviderServiceIds        = [
            'dataProvider1' => [
                ['class' => 'Test\DataProvider1', 'alias' => 'test_data_provider_name1']
            ],
            'dataProvider2' => [
                ['class' => 'Test\DataProvider2', 'alias' => 'test_data_provider_name2']
            ]
        ];

        $container->expects($this->exactly(4))
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::LAYOUT_FACTORY_BUILDER_SERVICE, true],
                        [ConfigurationPass::PHP_RENDERER_SERVICE, true],
                        [ConfigurationPass::TWIG_RENDERER_SERVICE, true],
                        [ConfigurationPass::LAYOUT_EXTENSION_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->exactly(2))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::LAYOUT_FACTORY_BUILDER_SERVICE, $factoryBuilderDef],
                        [ConfigurationPass::LAYOUT_EXTENSION_SERVICE, $extensionDef]
                    ]
                )
            );

        $factoryBuilderDef->expects($this->at(0))
            ->method('addMethodCall')
            ->with(
                'addRenderer',
                ['php', new Reference(ConfigurationPass::PHP_RENDERER_SERVICE)]
            );
        $factoryBuilderDef->expects($this->at(1))
            ->method('addMethodCall')
            ->with(
                'addRenderer',
                ['twig', new Reference(ConfigurationPass::TWIG_RENDERER_SERVICE)]
            );

        $container->expects($this->exactly(5))
            ->method('findTaggedServiceIds')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::BLOCK_TYPE_TAG_NAME, $blockTypeServiceIds],
                        [ConfigurationPass::BLOCK_TYPE_EXTENSION_TAG_NAME, $blockTypeExtensionServiceIds],
                        [ConfigurationPass::LAYOUT_UPDATE_TAG_NAME, $layoutUpdateServiceIds],
                        [ConfigurationPass::CONTEXT_CONFIGURATOR_TAG_NAME, $contextConfiguratorServiceIds],
                        [ConfigurationPass::DATA_PROVIDER_TAG_NAME, $dataProviderServiceIds]
                    ]
                )
            );

        $extensionDef->expects($this->at(0))
            ->method('replaceArgument')
            ->with(
                1,
                [
                    'test_block_name1' => 'block1',
                    'test_block_name2' => 'block2'
                ]
            );
        $extensionDef->expects($this->at(1))
            ->method('replaceArgument')
            ->with(
                2,
                [
                    'test_block_name1' => ['extension3', 'extension1'],
                    'test_block_name2' => ['extension2']
                ]
            );
        $extensionDef->expects($this->at(2))
            ->method('replaceArgument')
            ->with(
                3,
                [
                    'test_block_id1' => ['update3', 'update1'],
                    'test_block_id2' => ['update2']
                ]
            );
        $extensionDef->expects($this->at(3))
            ->method('replaceArgument')
            ->with(
                4,
                [
                    'contextConfigurator2',
                    'contextConfigurator1',
                    'contextConfigurator3'
                ]
            );
        $extensionDef->expects($this->at(4))
            ->method('replaceArgument')
            ->with(
                5,
                [
                    'test_data_provider_name1' => 'dataProvider1',
                    'test_data_provider_name2' => 'dataProvider2'
                ]
            );

        $compilerPass = new ConfigurationPass();
        $compilerPass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Tag attribute "alias" is required for "block1" service.
     */
    public function testBlockTypeWithoutAlias()
    {
        $container    = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $extensionDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $serviceIds = [
            'block1' => [['class' => 'Test\Class1']]
        ];

        $container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::LAYOUT_FACTORY_BUILDER_SERVICE, false],
                        [ConfigurationPass::LAYOUT_EXTENSION_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigurationPass::LAYOUT_EXTENSION_SERVICE)
            ->will($this->returnValue($extensionDef));

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConfigurationPass::BLOCK_TYPE_TAG_NAME)
            ->will($this->returnValue($serviceIds));

        $compilerPass = new ConfigurationPass();
        $compilerPass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Tag attribute "alias" is required for "extension1" service.
     */
    public function testBlockTypeExtensionWithoutAlias()
    {
        $container    = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $extensionDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $serviceIds = [
            'extension1' => [['class' => 'Test\Class1']]
        ];

        $container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::LAYOUT_FACTORY_BUILDER_SERVICE, false],
                        [ConfigurationPass::LAYOUT_EXTENSION_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigurationPass::LAYOUT_EXTENSION_SERVICE)
            ->will($this->returnValue($extensionDef));

        $container->expects($this->exactly(2))
            ->method('findTaggedServiceIds')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::BLOCK_TYPE_TAG_NAME, []],
                        [ConfigurationPass::BLOCK_TYPE_EXTENSION_TAG_NAME, $serviceIds]
                    ]
                )
            );

        $compilerPass = new ConfigurationPass();
        $compilerPass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Tag attribute "id" is required for "update1" service.
     */
    public function testLayoutUpdateWithoutId()
    {
        $container    = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $extensionDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $serviceIds = [
            'update1' => [['class' => 'Test\Class1']]
        ];

        $container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::LAYOUT_FACTORY_BUILDER_SERVICE, false],
                        [ConfigurationPass::LAYOUT_EXTENSION_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigurationPass::LAYOUT_EXTENSION_SERVICE)
            ->will($this->returnValue($extensionDef));

        $container->expects($this->exactly(3))
            ->method('findTaggedServiceIds')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::BLOCK_TYPE_TAG_NAME, []],
                        [ConfigurationPass::BLOCK_TYPE_EXTENSION_TAG_NAME, []],
                        [ConfigurationPass::LAYOUT_UPDATE_TAG_NAME, $serviceIds]
                    ]
                )
            );

        $compilerPass = new ConfigurationPass();
        $compilerPass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Tag attribute "alias" is required for "dataProvider1" service.
     */
    public function testDataProviderWithoutAlias()
    {
        $container    = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $extensionDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $serviceIds = [
            'dataProvider1' => [['class' => 'Test\DataProvider1']]
        ];

        $container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::LAYOUT_FACTORY_BUILDER_SERVICE, false],
                        [ConfigurationPass::LAYOUT_EXTENSION_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigurationPass::LAYOUT_EXTENSION_SERVICE)
            ->will($this->returnValue($extensionDef));

        $container->expects($this->exactly(5))
            ->method('findTaggedServiceIds')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::BLOCK_TYPE_TAG_NAME, []],
                        [ConfigurationPass::BLOCK_TYPE_EXTENSION_TAG_NAME, []],
                        [ConfigurationPass::LAYOUT_UPDATE_TAG_NAME, []],
                        [ConfigurationPass::CONTEXT_CONFIGURATOR_TAG_NAME, []],
                        [ConfigurationPass::DATA_PROVIDER_TAG_NAME, $serviceIds]
                    ]
                )
            );

        $compilerPass = new ConfigurationPass();
        $compilerPass->process($container);
    }
}
