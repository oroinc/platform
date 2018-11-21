<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Provider\CombinedConfigBag;
use Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Fixtures;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroApiExtensionTest extends \PHPUnit\Framework\TestCase
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
        $configExtensionRegistry->addExtension(new FiltersConfigExtension(new FilterOperatorRegistry([])));
        $configExtensionRegistry->addExtension(new SortersConfigExtension());

        return $configExtensionRegistry;
    }

    public function testLoadApiConfiguration()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $extension = new OroApiExtension();
        $extension->load([], $container);

        self::assertServiceExists($container, 'oro_api.config_bag_registry');
        self::assertServiceExists($container, 'oro_api.entity_exclusion_provider_registry');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver_registry');
        self::assertServiceExists($container, 'oro_api.entity_override_provider_registry');
        self::assertServiceExists($container, 'oro_api.config_bag.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.default');
        self::assertServiceExists($container, 'oro_api.entity_override_provider.default');
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
            ['api.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.default')->getArgument(4)
        );
        self::assertEquals(
            [
                ['oro_api.entity_alias_resolver.default', '']
            ],
            $container->getDefinition('oro_api.entity_alias_resolver_registry')->getArgument(0)
        );
        self::assertEquals(
            [
                ['oro_api.entity_override_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_override_provider_registry')->getArgument(0)
        );

        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.config_bag.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(1)
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
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.entity_override_provider.default')->getArgument(0)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoadApiConfigurationWithSeveralTypesOfConfigFilesInAdditionalToDefaultConfigFile()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');
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
        self::assertServiceExists($container, 'oro_api.entity_override_provider_registry');
        self::assertServiceExists($container, 'oro_api.config_bag.default');
        self::assertServiceExists($container, 'oro_api.config_bag.first');
        self::assertServiceExists($container, 'oro_api.config_bag.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.second');
        self::assertServiceExists($container, 'oro_api.entity_override_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_override_provider.first');
        self::assertServiceExists($container, 'oro_api.entity_override_provider.second');
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
            ['api_first.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.first')->getArgument(4)
        );
        self::assertEquals(
            ['api_second.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.second')->getArgument(4)
        );
        self::assertEquals(
            ['api.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.default')->getArgument(4)
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
            [
                ['oro_api.entity_override_provider.first', 'first'],
                ['oro_api.entity_override_provider.second', 'second'],
                ['oro_api.entity_override_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_override_provider_registry')->getArgument(0)
        );

        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.config_bag.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.config_bag.first')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
            $container->getDefinition('oro_api.config_bag.second')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.entity_alias_provider.first')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
            $container->getDefinition('oro_api.entity_alias_provider.second')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(1)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.config_entity_exclusion_provider.first')->getArgument(1)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
            $container->getDefinition('oro_api.config_entity_exclusion_provider.second')->getArgument(1)
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
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.entity_override_provider.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.entity_override_provider.first')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
            $container->getDefinition('oro_api.entity_override_provider.second')->getArgument(0)
        );
    }

    public function testLoadApiConfigurationShouldBeSortedByRequestType()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');
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
        self::assertServiceExists($container, 'oro_api.entity_override_provider_registry');

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
            ['api_several_request_types.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.several_request_types')->getArgument(4)
        );
        self::assertEquals(
            ['api_test.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.test')->getArgument(4)
        );
        self::assertEquals(
            ['api_another.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.another')->getArgument(4)
        );
        self::assertEquals(
            ['api.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.default')->getArgument(4)
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
        self::assertEquals(
            [
                ['oro_api.entity_override_provider.several_request_types', 'test1&test2'],
                ['oro_api.entity_override_provider.test', 'test'],
                ['oro_api.entity_override_provider.another', 'another'],
                ['oro_api.entity_override_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_override_provider_registry')->getArgument(0)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoadApiConfigurationForConfigWithSeveralConfigFiles()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');
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
        self::assertServiceExists($container, 'oro_api.entity_override_provider_registry');
        self::assertServiceExists($container, 'oro_api.config_bag.default');
        self::assertServiceExists($container, 'oro_api.config_bag.first');
        self::assertServiceExists($container, 'oro_api.config_bag.second');
        self::assertServiceExists($container, 'oro_api.config_bag.second_0_internal');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_cache.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_provider.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_loader.second');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.default');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.first');
        self::assertServiceExists($container, 'oro_api.entity_alias_resolver.second');
        self::assertServiceExists($container, 'oro_api.entity_override_provider.default');
        self::assertServiceExists($container, 'oro_api.entity_override_provider.first');
        self::assertServiceExists($container, 'oro_api.entity_override_provider.second');
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
            ['api_first.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.first')->getArgument(4)
        );
        self::assertEquals(
            ['api_second.yml', 'api_first.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.second')->getArgument(4)
        );
        self::assertEquals(
            ['api.yml'],
            $container->getDefinition('oro_api.entity_alias_resolver.default')->getArgument(4)
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
            [
                ['oro_api.entity_override_provider.first', 'first'],
                ['oro_api.entity_override_provider.second', 'second'],
                ['oro_api.entity_override_provider.default', '']
            ],
            $container->getDefinition('oro_api.entity_override_provider_registry')->getArgument(0)
        );

        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.config_bag.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.config_bag.first')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
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
                    new Reference('oro_api.config_bag.second_1_internal')
                ],
                new Reference('oro_api.config_merger.entity'),
                new Reference('oro_api.config_merger.relation')
            ],
            $container->getDefinition('oro_api.config_bag.second')->getArguments()
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.entity_alias_provider.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.entity_alias_provider.first')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
            $container->getDefinition('oro_api.entity_alias_provider.second')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.config_entity_exclusion_provider.default')->getArgument(1)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.config_entity_exclusion_provider.first')->getArgument(1)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
            $container->getDefinition('oro_api.config_entity_exclusion_provider.second')->getArgument(1)
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
        self::assertEquals(
            new Reference('oro_api.config_cache.default'),
            $container->getDefinition('oro_api.entity_override_provider.default')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.first'),
            $container->getDefinition('oro_api.entity_override_provider.first')->getArgument(0)
        );
        self::assertEquals(
            new Reference('oro_api.config_cache.second'),
            $container->getDefinition('oro_api.entity_override_provider.second')->getArgument(0)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid configuration for path "oro_api.config_files": The "request_type" options for "test1" and "test2" are duplicated.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadApiConfigurationShouldThrowExceptionIfExistSeveralConfigurationsWithSameRequestType()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');
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
    public function testLoadApiConfigurationShouldThrowExceptionIfExistConfigurationsWithSameRequestTypeAsDefaultOne()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');
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

    public function testRegisterConfigParameters()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config = [
            'config_max_nesting_level' => 2,
            'config_files'             => [
                'first'  => [
                    'file_name'    => 'api_first.yml',
                    'request_type' => ['first']
                ],
                'second' => [
                    'file_name'    => ['api_second.yml', 'api_first.yml'],
                    'request_type' => ['second']
                ]
            ],
            'api_doc_views'            => [
                'view_1'       => [
                    'label'        => 'View 1',
                    'request_type' => ['first', 'rest']
                ],
                'default_view' => [
                    'label'   => 'Default View',
                    'default' => true
                ]
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config], $container);

        self::assertEquals(
            ['view_1', 'default_view'],
            $container->getParameter('oro_api.api_doc.views')
        );
        self::assertEquals(
            'default_view',
            $container->getParameter('oro_api.api_doc.default_view')
        );

        self::assertServiceExists($container, 'oro_api.config_extension_registry');
        self::assertEquals(
            2,
            $container->getDefinition('oro_api.config_extension_registry')->getArgument(0)
        );

        self::assertServiceExists($container, 'oro_api.config_cache_warmer');
        self::assertEquals(
            [
                'first'   => ['api_first.yml'],
                'second'  => ['api_second.yml', 'api_first.yml'],
                'default' => ['api.yml']
            ],
            $container->getDefinition('oro_api.config_cache_warmer')->getArgument(0)
        );

        self::assertServiceExists($container, 'oro_api.cache_manager');
        self::assertEquals(
            [
                'first'   => ['first'],
                'second'  => ['second'],
                'default' => []
            ],
            $container->getDefinition('oro_api.cache_manager')->getArgument(0)
        );
        self::assertEquals(
            [
                'view_1'       => ['first', 'rest'],
                'default_view' => []
            ],
            $container->getDefinition('oro_api.cache_manager')->getArgument(1)
        );
    }

    public function testConfigurationForEmptyCors()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config = [];

        $extension = new OroApiExtension();
        $extension->load([$config], $container);

        self::assertSame(
            600,
            $container->getDefinition('oro_api.options.rest.set_cache_control')->getArgument(0)
        );
        self::assertSame(
            600,
            $container->getDefinition('oro_api.options.rest.cors.set_max_age')->getArgument(0)
        );
        self::assertSame(
            [],
            $container->getDefinition('oro_api.rest.cors.set_allow_origin')->getArgument(0)
        );
        self::assertSame(
            [],
            $container->getDefinition('oro_api.rest.cors.set_allow_and_expose_headers')->getArgument(0)
        );
        self::assertSame(
            [],
            $container->getDefinition('oro_api.rest.cors.set_allow_and_expose_headers')->getArgument(1)
        );
        self::assertSame(
            false,
            $container->getDefinition('oro_api.rest.cors.set_allow_and_expose_headers')->getArgument(2)
        );
    }

    public function testConfigurationForCors()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config = [
            'cors' => [
                'preflight_max_age' => 123,
                'allow_origins'     => ['https://foo.com'],
                'allow_headers'     => ['AllowHeader1'],
                'expose_headers'    => ['ExposeHeader1'],
                'allow_credentials' => true
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config], $container);

        self::assertSame(
            $config['cors']['preflight_max_age'],
            $container->getDefinition('oro_api.options.rest.set_cache_control')->getArgument(0)
        );
        self::assertSame(
            $config['cors']['preflight_max_age'],
            $container->getDefinition('oro_api.options.rest.cors.set_max_age')->getArgument(0)
        );
        self::assertSame(
            $config['cors']['allow_origins'],
            $container->getDefinition('oro_api.rest.cors.set_allow_origin')->getArgument(0)
        );
        self::assertSame(
            $config['cors']['allow_headers'],
            $container->getDefinition('oro_api.rest.cors.set_allow_and_expose_headers')->getArgument(0)
        );
        self::assertSame(
            $config['cors']['expose_headers'],
            $container->getDefinition('oro_api.rest.cors.set_allow_and_expose_headers')->getArgument(1)
        );
        self::assertSame(
            $config['cors']['allow_credentials'],
            $container->getDefinition('oro_api.rest.cors.set_allow_and_expose_headers')->getArgument(2)
        );
    }

    public function testConfigurationForApiDocViews()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', false);
        $container->set('oro_api.config_extension_registry', $this->getConfigExtensionRegistry());

        $config = [
            'documentation_path' => 'http://test.com/default_api_docs',
            'api_doc_views'      => [
                'view1' => [
                    'label'        => 'View 1',
                    'default'      => true,
                    'request_type' => ['rest', 'json_api'],
                    'headers'      => [
                        'Content-Type' => 'application/vnd.api+json',
                        'X-Include'    => [
                            ['value' => 'totalCount', 'actions' => ['get_list']],
                            ['value' => 'forAllActions']
                        ]
                    ]
                ],
                'view2' => [
                    'documentation_path' => 'http://test.com/api_docs_for_view2',
                    'html_formatter'     => 'another_html_formatter',
                    'sandbox'            => false
                ],
                'view3' => []
            ]
        ];

        $extension = new OroApiExtension();
        $extension->load([$config], $container);

        $apiConfig = DependencyInjectionUtil::getConfig($container);
        self::assertEquals(
            [
                'view1' => [
                    'label'              => 'View 1',
                    'default'            => true,
                    'request_type'       => ['rest', 'json_api'],
                    'documentation_path' => 'http://test.com/default_api_docs',
                    'html_formatter'     => 'oro_api.api_doc.formatter.html_formatter',
                    'sandbox'            => true,
                    'headers'            => [
                        'Content-Type' => [
                            ['value' => 'application/vnd.api+json', 'actions' => []]
                        ],
                        'X-Include'    => [
                            ['value' => 'totalCount', 'actions' => ['get_list']],
                            ['value' => 'forAllActions', 'actions' => []]
                        ]
                    ]
                ],
                'view2' => [
                    'default'            => false,
                    'request_type'       => [],
                    'documentation_path' => 'http://test.com/api_docs_for_view2',
                    'html_formatter'     => 'another_html_formatter',
                    'sandbox'            => false,
                    'headers'            => []
                ],
                'view3' => [
                    'default'            => false,
                    'request_type'       => [],
                    'documentation_path' => 'http://test.com/default_api_docs',
                    'html_formatter'     => 'oro_api.api_doc.formatter.html_formatter',
                    'sandbox'            => true,
                    'headers'            => []
                ]
            ],
            $apiConfig['api_doc_views']
        );
    }
}
