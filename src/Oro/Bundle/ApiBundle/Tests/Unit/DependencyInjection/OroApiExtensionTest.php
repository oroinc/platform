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
        $container->set('oro_api.config_extension_registry', $configExtensionRegistry);

        $extension->load([], $container);

        $this->assertNotNull(
            $container->getDefinition('oro_api.config_bag'),
            'Expected oro_api.config_bag service'
        );
        $this->assertNotNull(
            $container->getDefinition('oro_api.entity_exclusion_provider.config'),
            'Expected oro_api.entity_exclusion_provider.config service'
        );
        $this->assertNotNull(
            $container->getDefinition('oro_api.entity_exclusion_provider'),
            'Expected oro_api.entity_exclusion_provider service'
        );

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
                                                'disable_partial_load' => true
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
            $container->getDefinition('oro_api.config_bag')->getArgument(0)
        );

        $this->assertEquals(
            [
                ['entity' => 'Test\Entity1'],
                ['entity' => 'Test\Entity2'],
                ['entity' => 'Test\Entity3'],
            ],
            $container->getDefinition('oro_api.entity_exclusion_provider.config')->getArgument(1)
        );
        $this->assertEquals(
            [
                ['entity' => 'Test\Entity12'],
                ['entity' => 'Test\Entity12', 'field' => 'field1'],
            ],
            $container->getDefinition('oro_api.entity_exclusion_provider')->getArgument(1)
        );
    }
}
