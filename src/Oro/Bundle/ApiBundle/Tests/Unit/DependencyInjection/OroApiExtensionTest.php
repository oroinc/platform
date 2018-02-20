<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension;
use Oro\Bundle\ApiBundle\Provider\CombinedConfigBag;
use Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Fixtures;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroApiExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $bundle1 = new Fixtures\BarBundle\BarBundle();
        $bundle2 = new Fixtures\BazBundle\BazBundle();
        $bundle3 = new Fixtures\FooBundle\FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(
                [
                    $bundle1->getName() => get_class($bundle1),
                    $bundle2->getName() => get_class($bundle2),
                    $bundle3->getName() => get_class($bundle3)
                ]
            );
    }

    protected function tearDown()
    {
        CumulativeResourceManager::getInstance()->clear();
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     */
    private static function assertServiceExists(ContainerBuilder $container, $serviceId)
    {
        self::assertNotNull(
            $container->getDefinition($serviceId),
            sprintf('Expected "%s" service', $serviceId)
        );
    }

    /**
     * @return ConfigExtensionRegistry
     */
    private function getConfigExtensionRegistry()
    {
        $configExtensionRegistry = new ConfigExtensionRegistry(3);
        $configExtensionRegistry->addExtension(new FiltersConfigExtension());
        $configExtensionRegistry->addExtension(new SortersConfigExtension());

        return $configExtensionRegistry;
    }

    /**
     * @return array
     */
    private function getDefaultApiConfig()
    {
        return [
            'entities'  => [
                'Test\Entity1'  => [],
                'Test\Entity2'  => [],
                'Test\Entity3'  => [],
                'Test\Entity4'  => [
                    'fields'  => [
                        'field1' => [],
                        'field2' => [
                            'exclude' => true
                        ],
                        'field3' => [
                            'exclude'  => true,
                            'order_by' => ['name' => 'ASC']
                        ],
                        'field4' => [
                            'fields' => [
                                'field41' => [
                                    'order_by' => ['name' => 'DESC']
                                ]
                            ]
                        ],
                        'field5' => [
                            'fields' => [
                                'field51' => [
                                    'fields' => [
                                        'field511' => [
                                            'hints' => [['name' => 'HINT_TRANSLATABLE']]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'filters' => [
                        'fields' => [
                            'field1' => [],
                            'field2' => [
                                'data_type' => 'string',
                                'exclude'   => true
                            ],
                            'field3' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                    'sorters' => [
                        'fields' => [
                            'field1' => [],
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ],
                'Test\Entity5'  => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
                'Test\Entity6'  => [
                    'fields' => [
                        'field1' => []
                    ]
                ],
                'Test\Entity7'  => [
                    'documentation_resource' => ['entity7_bar.md', 'entity7_foo.md']
                ],
                'Test\Entity10' => [],
                'Test\Entity11' => [],
                'Test\Entity12' => [
                    'fields' => [
                        'field1' => [
                            'exclude' => false
                        ]
                    ]
                ]
            ],
            'relations' => []
        ];
    }

    /**
     * @return array
     */
    private function getDefaultAntityAliases()
    {
        return [
            'Test\Entity4' => [
                'alias'        => 'entity4',
                'plural_alias' => 'entity4_plural'
            ],
            'Test\Entity5' => [
                'alias'        => 'entity5',
                'plural_alias' => 'entity5_plural'
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoadApiConfiguration()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $extension = new OroApiExtension();
        $extension->load([], $container);

        self::assertServiceExists($container, 'oro_api.config_bag_registry');
        self::assertServiceExists($container, 'oro_api.entity_exclusion_provider_registry');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver_registry');
        self::assertServiceExists($container, 'oro_api.config_bag.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.default');
        self::assertServiceExists($container, 'oro_api.config_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.aliased_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.chain_entity_exclusion_provider.default');

        self::assertEquals(
            [
                ['oro_api.config_bag.default', '']
            ],
            $container->getDefinition('oro_api.config_bag_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.chain_entity_exclusion_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_exclusion_provider_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.entity_alias_resolver.default', '']
            ],
            $container->getDefinition('oro_api.entity_alias_resolver_registry')->getArgument(0)
        );

        self::assertEquals(
            $this->getDefaultApiConfig(),
            $container->getDefinition('oro_api.config_bag.default')->getArgument(0)
        );
        self::assertEquals(
            $this->getDefaultAntityAliases(),
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(0)
        );
        self::assertEquals(
            ['Test\Entity1', 'Test\Entity2', 'Test\Entity3'],
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity2'],
                ['entity' => 'Test\Entity3']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity12'],
                ['entity' => 'Test\Entity12', 'field' => 'field1']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(2)
        );

        self::assertEquals(
            [
                ['setNamespace', ['oro_api_aliases_default']]
            ],
            $container->getDefinition('oro_api.entity_alias_cache.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addEntityAliasProvider', [new Reference('oro_api.entity_alias_provider.default')]],
                ['addEntityClassProvider', [new Reference('oro_api.entity_alias_provider.default')]]
            ],
            $container->getDefinition('oro_api.entity_alias_loader.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addProvider', [new Reference('oro_api.config_entity_exclusion_provider.default')]],
                ['addProvider', [new Reference('oro_api.aliased_entity_exclusion_provider.default')]],
                ['addProvider', [new Reference('oro_api.entity_exclusion_provider.shared')]]
            ],
            $container->getDefinition('oro_api.chain_entity_exclusion_provider.default')->getMethodCalls()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoadApiConfigurationWithSeveralTypesOfConfigFilesInAdditionalToDefaultConfigFile()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config1 = [
            'config_files' => [
                'first' => [
                    'file_name'    => 'api_first.yml',
                    'request_type' => ['first']
                ]
            ]
        ];
        $config2 = [
            'config_files' => [
                'second' => [
                    'file_name'    => 'api_second.yml',
                    'request_type' => ['second']
                ]
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config1, $config2], $container);

        self::assertServiceExists($container, 'oro_api.config_bag_registry');
        self::assertServiceExists($container, 'oro_api.entity_exclusion_provider_registry');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver_registry');
        self::assertServiceExists($container, 'oro_api.config_bag.default');
        self::assertServiceExists($container, 'oro_api.config_bag.first');
        self::assertServiceExists($container, 'oro_api.config_bag.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.second');
        self::assertServiceExists($container, 'oro_api.config_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.config_entity_exclusion_provider.first');
        self::assertServiceExists($container, 'oro_api.config_entity_exclusion_provider.second');
        self::assertServiceExists($container, 'oro_api.aliased_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.aliased_entity_exclusion_provider.first');
        self::assertServiceExists($container, 'oro_api.aliased_entity_exclusion_provider.second');
        self::assertServiceExists($container, 'oro_api.chain_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.chain_entity_exclusion_provider.first');
        self::assertServiceExists($container, 'oro_api.chain_entity_exclusion_provider.second');

        self::assertEquals(
            [
                ['oro_api.config_bag.first', 'first'],
                ['oro_api.config_bag.second', 'second'],
                ['oro_api.config_bag.default', '']
            ],
            $container->getDefinition('oro_api.config_bag_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.chain_entity_exclusion_provider.first', 'first'],
                ['oro_api.chain_entity_exclusion_provider.second', 'second'],
                ['oro_api.chain_entity_exclusion_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_exclusion_provider_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.entity_alias_resolver.first', 'first'],
                ['oro_api.entity_alias_resolver.second', 'second'],
                ['oro_api.entity_alias_resolver.default', '']
            ],
            $container->getDefinition('oro_api.entity_alias_resolver_registry')->getArgument(0)
        );

        self::assertEquals(
            $this->getDefaultApiConfig(),
            $container->getDefinition('oro_api.config_bag.default')->getArgument(0)
        );
        self::assertEquals(
            [
                'entities'  => [
                    'Test\Entity1'  => [],
                    'Test\Entity2'  => [
                        'fields' => [
                            'field1' => [],
                            'field2' => [
                                'exclude' => false
                            ]
                        ]
                    ],
                    'Test\Entity3'  => [],
                    'Test\Entity4'  => [],
                    'Test\Entity5'  => [],
                    'Test\Entity6'  => [],
                    'Test\Entity10' => [],
                    'Test\Entity11' => []
                ],
                'relations' => []
            ],
            $container->getDefinition('oro_api.config_bag.first')->getArgument(0)
        );
        self::assertEquals(
            [
                'entities'  => [
                    'Test\Entity1'  => [],
                    'Test\Entity2'  => [
                        'fields' => [
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                    'Test\Entity3'  => [],
                    'Test\Entity4'  => [],
                    'Test\Entity5'  => [],
                    'Test\Entity6'  => [],
                    'Test\Entity12' => [],
                    'Test\Entity13' => []
                ],
                'relations' => []
            ],
            $container->getDefinition('oro_api.config_bag.second')->getArgument(0)
        );
        self::assertEquals(
            $this->getDefaultAntityAliases(),
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(0)
        );
        self::assertEquals(
            [
                'Test\Entity2' => [
                    'alias'        => 'entity2',
                    'plural_alias' => 'entity2_plural'
                ]
            ],
            $container->getDefinition('oro_api.entity_alias_provider.first')->getArgument(0)
        );
        self::assertEquals(
            [],
            $container->getDefinition('oro_api.entity_alias_provider.second')->getArgument(0)
        );
        self::assertEquals(
            ['Test\Entity1', 'Test\Entity2', 'Test\Entity3'],
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(1)
        );
        self::assertEquals(
            ['Test\Entity1', 'Test\Entity4', 'Test\Entity6', 'Test\Entity11'],
            $container->getDefinition('oro_api.entity_alias_provider.first')->getArgument(1)
        );
        self::assertEquals(
            ['Test\Entity4', 'Test\Entity5', 'Test\Entity13'],
            $container->getDefinition('oro_api.entity_alias_provider.second')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity2'],
                ['entity' => 'Test\Entity3']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity4'],
                ['entity' => 'Test\Entity6'],
                ['entity' => 'Test\Entity11']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.first')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity4'],
                ['entity' => 'Test\Entity5'],
                ['entity' => 'Test\Entity13']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.second')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity12'],
                ['entity' => 'Test\Entity12', 'field' => 'field1']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(2)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity2', 'field' => 'field2'],
                ['entity' => 'Test\Entity3'],
                ['entity' => 'Test\Entity5'],
                ['entity' => 'Test\Entity10']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.first')->getArgument(2)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity3'],
                ['entity' => 'Test\Entity6'],
                ['entity' => 'Test\Entity12']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.second')->getArgument(2)
        );

        self::assertEquals(
            [
                ['setNamespace', ['oro_api_aliases_default']]
            ],
            $container->getDefinition('oro_api.entity_alias_cache.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['setNamespace', ['oro_api_aliases_first']]
            ],
            $container->getDefinition('oro_api.entity_alias_cache.first')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['setNamespace', ['oro_api_aliases_second']]
            ],
            $container->getDefinition('oro_api.entity_alias_cache.second')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addEntityAliasProvider', [new Reference('oro_api.entity_alias_provider.default')]],
                ['addEntityClassProvider', [new Reference('oro_api.entity_alias_provider.default')]]
            ],
            $container->getDefinition('oro_api.entity_alias_loader.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addEntityAliasProvider', [new Reference('oro_api.entity_alias_provider.first')]],
                ['addEntityClassProvider', [new Reference('oro_api.entity_alias_provider.first')]]
            ],
            $container->getDefinition('oro_api.entity_alias_loader.first')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addEntityAliasProvider', [new Reference('oro_api.entity_alias_provider.second')]],
                ['addEntityClassProvider', [new Reference('oro_api.entity_alias_provider.second')]]
            ],
            $container->getDefinition('oro_api.entity_alias_loader.second')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addProvider', [new Reference('oro_api.config_entity_exclusion_provider.default')]],
                ['addProvider', [new Reference('oro_api.aliased_entity_exclusion_provider.default')]],
                ['addProvider', [new Reference('oro_api.entity_exclusion_provider.shared')]]
            ],
            $container->getDefinition('oro_api.chain_entity_exclusion_provider.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addProvider', [new Reference('oro_api.config_entity_exclusion_provider.first')]],
                ['addProvider', [new Reference('oro_api.aliased_entity_exclusion_provider.first')]],
                ['addProvider', [new Reference('oro_api.entity_exclusion_provider.shared')]]
            ],
            $container->getDefinition('oro_api.chain_entity_exclusion_provider.first')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addProvider', [new Reference('oro_api.config_entity_exclusion_provider.second')]],
                ['addProvider', [new Reference('oro_api.aliased_entity_exclusion_provider.second')]],
                ['addProvider', [new Reference('oro_api.entity_exclusion_provider.shared')]]
            ],
            $container->getDefinition('oro_api.chain_entity_exclusion_provider.second')->getMethodCalls()
        );
    }

    public function testLoadApiConfigurationShouldBeSortedByRequestType()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config = [
            'config_files' => [
                'default'               => [
                    'request_type' => []
                ],
                'test'                  => [
                    'file_name'    => 'api_test.yml',
                    'request_type' => ['test']
                ],
                'several_request_types' => [
                    'file_name'    => 'api_several_request_types.yml',
                    'request_type' => ['test1', 'test2']
                ],
                'another'               => [
                    'file_name'    => 'api_another.yml',
                    'request_type' => ['another']
                ]
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config], $container);

        self::assertServiceExists($container, 'oro_api.config_bag_registry');
        self::assertServiceExists($container, 'oro_api.entity_exclusion_provider_registry');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver_registry');

        self::assertEquals(
            [
                ['oro_api.config_bag.several_request_types', 'test1&test2'],
                ['oro_api.config_bag.test', 'test'],
                ['oro_api.config_bag.another', 'another'],
                ['oro_api.config_bag.default', '']
            ],
            $container->getDefinition('oro_api.config_bag_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.chain_entity_exclusion_provider.several_request_types', 'test1&test2'],
                ['oro_api.chain_entity_exclusion_provider.test', 'test'],
                ['oro_api.chain_entity_exclusion_provider.another', 'another'],
                ['oro_api.chain_entity_exclusion_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_exclusion_provider_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.entity_alias_resolver.several_request_types', 'test1&test2'],
                ['oro_api.entity_alias_resolver.test', 'test'],
                ['oro_api.entity_alias_resolver.another', 'another'],
                ['oro_api.entity_alias_resolver.default', '']
            ],
            $container->getDefinition('oro_api.entity_alias_resolver_registry')->getArgument(0)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoadApiConfigurationForConfigWithSeveralConfigFiles()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config1 = [
            'config_files' => [
                'first' => [
                    'file_name'    => 'api_first.yml',
                    'request_type' => ['first']
                ]
            ]
        ];
        $config2 = [
            'config_files' => [
                'second' => [
                    'file_name'    => ['api_second.yml', 'api_first.yml'],
                    'request_type' => ['second']
                ]
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config1, $config2], $container);

        self::assertServiceExists($container, 'oro_api.config_bag_registry');
        self::assertServiceExists($container, 'oro_api.entity_exclusion_provider_registry');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver_registry');
        self::assertServiceExists($container, 'oro_api.config_bag.default');
        self::assertServiceExists($container, 'oro_api.config_bag.first');
        self::assertServiceExists($container, 'oro_api.config_bag.second');
        self::assertServiceExists($container, 'oro_api.config_bag.second_0_internal');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.second');
        self::assertServiceExists($container, 'oro_api.config_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.config_entity_exclusion_provider.first');
        self::assertServiceExists($container, 'oro_api.config_entity_exclusion_provider.second');
        self::assertServiceExists($container, 'oro_api.aliased_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.aliased_entity_exclusion_provider.first');
        self::assertServiceExists($container, 'oro_api.aliased_entity_exclusion_provider.second');
        self::assertServiceExists($container, 'oro_api.chain_entity_exclusion_provider.default');
        self::assertServiceExists($container, 'oro_api.chain_entity_exclusion_provider.first');
        self::assertServiceExists($container, 'oro_api.chain_entity_exclusion_provider.second');

        self::assertEquals(
            [
                ['oro_api.config_bag.first', 'first'],
                ['oro_api.config_bag.second', 'second'],
                ['oro_api.config_bag.default', '']
            ],
            $container->getDefinition('oro_api.config_bag_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.chain_entity_exclusion_provider.first', 'first'],
                ['oro_api.chain_entity_exclusion_provider.second', 'second'],
                ['oro_api.chain_entity_exclusion_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_exclusion_provider_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.entity_alias_resolver.first', 'first'],
                ['oro_api.entity_alias_resolver.second', 'second'],
                ['oro_api.entity_alias_resolver.default', '']
            ],
            $container->getDefinition('oro_api.entity_alias_resolver_registry')->getArgument(0)
        );

        self::assertEquals(
            $this->getDefaultApiConfig(),
            $container->getDefinition('oro_api.config_bag.default')->getArgument(0)
        );
        self::assertEquals(
            [
                'entities'  => [
                    'Test\Entity1'  => [],
                    'Test\Entity2'  => [
                        'fields' => [
                            'field1' => [],
                            'field2' => [
                                'exclude' => false
                            ]
                        ]
                    ],
                    'Test\Entity3'  => [],
                    'Test\Entity4'  => [],
                    'Test\Entity5'  => [],
                    'Test\Entity6'  => [],
                    'Test\Entity10' => [],
                    'Test\Entity11' => []
                ],
                'relations' => []
            ],
            $container->getDefinition('oro_api.config_bag.first')->getArgument(0)
        );
        self::assertEquals(
            [
                'entities'  => [
                    'Test\Entity1'  => [],
                    'Test\Entity2'  => [
                        'fields' => [
                            'field2' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                    'Test\Entity3'  => [],
                    'Test\Entity4'  => [],
                    'Test\Entity5'  => [],
                    'Test\Entity6'  => [],
                    'Test\Entity12' => [],
                    'Test\Entity13' => []
                ],
                'relations' => []
            ],
            $container->getDefinition('oro_api.config_bag.second_0_internal')->getArgument(0)
        );
        self::assertEquals(
            CombinedConfigBag::class,
            $container->getDefinition('oro_api.config_bag.second')->getClass()
        );
        self::assertEquals(
            [
                [
                    new Reference('oro_api.config_bag.second_0_internal'),
                    new Reference('oro_api.config_bag.first')
                ],
                new Reference('oro_api.config_merger.entity'),
                new Reference('oro_api.config_merger.relation')
            ],
            $container->getDefinition('oro_api.config_bag.second')->getArguments()
        );
        self::assertEquals(
            $this->getDefaultAntityAliases(),
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(0)
        );
        self::assertEquals(
            [
                'Test\Entity2' => [
                    'alias'        => 'entity2',
                    'plural_alias' => 'entity2_plural'
                ]
            ],
            $container->getDefinition('oro_api.entity_alias_provider.first')->getArgument(0)
        );
        self::assertEquals(
            [
                'Test\Entity2' => [
                    'alias'        => 'entity2',
                    'plural_alias' => 'entity2_plural'
                ]
            ],
            $container->getDefinition('oro_api.entity_alias_provider.second')->getArgument(0)
        );
        self::assertEquals(
            ['Test\Entity1', 'Test\Entity2', 'Test\Entity3'],
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(1)
        );
        self::assertEquals(
            ['Test\Entity1', 'Test\Entity4', 'Test\Entity6', 'Test\Entity11'],
            $container->getDefinition('oro_api.entity_alias_provider.first')->getArgument(1)
        );
        self::assertEquals(
            ['Test\Entity4', 'Test\Entity5', 'Test\Entity13', 'Test\Entity1', 'Test\Entity6', 'Test\Entity11'],
            $container->getDefinition('oro_api.entity_alias_provider.second')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity2'],
                ['entity' => 'Test\Entity3']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity4'],
                ['entity' => 'Test\Entity6'],
                ['entity' => 'Test\Entity11']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.first')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity4'],
                ['entity' => 'Test\Entity5'],
                ['entity' => 'Test\Entity13'],
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity6'],
                ['entity' => 'Test\Entity11']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.second')->getArgument(1)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity12'],
                ['entity' => 'Test\Entity12', 'field' => 'field1']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(2)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity2', 'field' => 'field2'],
                ['entity' => 'Test\Entity3'],
                ['entity' => 'Test\Entity5'],
                ['entity' => 'Test\Entity10']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.first')->getArgument(2)
        );
        self::assertEquals(
            [
                ['entity' => 'Test\Entity3'],
                ['entity' => 'Test\Entity6'],
                ['entity' => 'Test\Entity12'],
                ['entity' => 'Test\Entity2', 'field' => 'field2'],
                ['entity' => 'Test\Entity5'],
                ['entity' => 'Test\Entity10']
            ],
            $container->getDefinition('oro_api.config_entity_exclusion_provider.second')->getArgument(2)
        );

        self::assertEquals(
            [
                ['setNamespace', ['oro_api_aliases_default']]
            ],
            $container->getDefinition('oro_api.entity_alias_cache.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['setNamespace', ['oro_api_aliases_first']]
            ],
            $container->getDefinition('oro_api.entity_alias_cache.first')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['setNamespace', ['oro_api_aliases_second']]
            ],
            $container->getDefinition('oro_api.entity_alias_cache.second')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addEntityAliasProvider', [new Reference('oro_api.entity_alias_provider.default')]],
                ['addEntityClassProvider', [new Reference('oro_api.entity_alias_provider.default')]]
            ],
            $container->getDefinition('oro_api.entity_alias_loader.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addEntityAliasProvider', [new Reference('oro_api.entity_alias_provider.first')]],
                ['addEntityClassProvider', [new Reference('oro_api.entity_alias_provider.first')]]
            ],
            $container->getDefinition('oro_api.entity_alias_loader.first')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addEntityAliasProvider', [new Reference('oro_api.entity_alias_provider.second')]],
                ['addEntityClassProvider', [new Reference('oro_api.entity_alias_provider.second')]]
            ],
            $container->getDefinition('oro_api.entity_alias_loader.second')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addProvider', [new Reference('oro_api.config_entity_exclusion_provider.default')]],
                ['addProvider', [new Reference('oro_api.aliased_entity_exclusion_provider.default')]],
                ['addProvider', [new Reference('oro_api.entity_exclusion_provider.shared')]]
            ],
            $container->getDefinition('oro_api.chain_entity_exclusion_provider.default')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addProvider', [new Reference('oro_api.config_entity_exclusion_provider.first')]],
                ['addProvider', [new Reference('oro_api.aliased_entity_exclusion_provider.first')]],
                ['addProvider', [new Reference('oro_api.entity_exclusion_provider.shared')]]
            ],
            $container->getDefinition('oro_api.chain_entity_exclusion_provider.first')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addProvider', [new Reference('oro_api.config_entity_exclusion_provider.second')]],
                ['addProvider', [new Reference('oro_api.aliased_entity_exclusion_provider.second')]],
                ['addProvider', [new Reference('oro_api.entity_exclusion_provider.shared')]]
            ],
            $container->getDefinition('oro_api.chain_entity_exclusion_provider.second')->getMethodCalls()
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "oro_api.config_files": The "request_type" options for "test1" and "test2" are duplicated.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadApiConfigurationShouldThrowExceptionIfExistSeveralConfugurationsWithSameRequestType()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config = [
            'config_files' => [
                'test1' => [
                    'file_name'    => ['api_test1.yml'],
                    'request_type' => ['test1', 'test2']
                ],
                'test2' => [
                    'file_name'    => ['api_test2.yml'],
                    'request_type' => ['test2', 'test1']
                ]
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config], $container);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "oro_api.config_files": The "request_type" options for "test2" and "default" are duplicated.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadApiConfigurationShouldThrowExceptionIfExistConfugurationsWithSameRequestTypeAsDefailtOne()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config = [
            'config_files' => [
                'test1' => [
                    'file_name'    => ['api_test1.yml'],
                    'request_type' => ['test1']
                ],
                'test2' => [
                    'file_name'    => ['api_test2.yml'],
                    'request_type' => []
                ]
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config], $container);
    }
}
