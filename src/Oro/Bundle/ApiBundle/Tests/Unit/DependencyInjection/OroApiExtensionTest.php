<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension;
use Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Fixtures;

class OroApiExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoadApiConfiguration()
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

        $extension = new OroApiExtension();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $configExtensionRegistry = new ConfigExtensionRegistry(3);
        $configExtensionRegistry->addExtension(new FiltersConfigExtension());
        $configExtensionRegistry->addExtension(new SortersConfigExtension());
        $container->set(OroApiExtension::CONFIG_EXTENSION_REGISTRY_SERVICE_ID, $configExtensionRegistry);

        $extension->load([], $container);

        $this->assertServiceExists($container, OroApiExtension::CONFIG_BAG_SERVICE_ID);
        $this->assertServiceExists($container, OroApiExtension::ENTITY_ALIAS_PROVIDER_SERVICE_ID);
        $this->assertServiceExists($container, OroApiExtension::CONFIG_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID);
        $this->assertServiceExists($container, OroApiExtension::ENTITY_EXCLUSION_PROVIDER_SERVICE_ID);

        $this->assertEquals(
            [
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
                        ],
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
                    'Test\Entity10' => [],
                    'Test\Entity11' => [],
                    'Test\Entity12' => [
                        'fields' => [
                            'field1' => [
                                'exclude' => false
                            ]
                        ]
                    ],
                ],
                'relations' => [],
            ],
            $container->getDefinition(OroApiExtension::CONFIG_BAG_SERVICE_ID)->getArgument(0)
        );

        $this->assertEquals(
            [
                'Test\Entity4' => [
                    'alias'        => 'entity4',
                    'plural_alias' => 'entity4_plural'
                ],
                'Test\Entity5' => [
                    'alias'        => 'entity5',
                    'plural_alias' => 'entity5_plural'
                ],
            ],
            $container->getDefinition(OroApiExtension::ENTITY_ALIAS_PROVIDER_SERVICE_ID)->getArgument(0)
        );
        $this->assertEquals(
            [
                'Test\Entity1',
                'Test\Entity2',
                'Test\Entity3',
            ],
            $container->getDefinition(OroApiExtension::ENTITY_ALIAS_PROVIDER_SERVICE_ID)->getArgument(1)
        );

        $this->assertEquals(
            [
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity2'],
                ['entity' => 'Test\Entity3'],
            ],
            $container->getDefinition(OroApiExtension::CONFIG_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID)->getArgument(1)
        );

        $this->assertEquals(
            [
                ['entity' => 'Test\Entity12'],
                ['entity' => 'Test\Entity12', 'field' => 'field1'],
            ],
            $container->getDefinition(OroApiExtension::ENTITY_EXCLUSION_PROVIDER_SERVICE_ID)->getArgument(1)
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $serviceId
     */
    protected function assertServiceExists(ContainerBuilder $container, $serviceId)
    {
        $this->assertNotNull(
            $container->getDefinition($serviceId),
            sprintf('Expected "%s" service', $serviceId)
        );
    }
}
