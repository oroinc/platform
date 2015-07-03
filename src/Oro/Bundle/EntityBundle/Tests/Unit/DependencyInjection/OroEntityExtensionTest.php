<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\EntityBundle\DependencyInjection\OroEntityExtension;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;

use Oro\Component\Config\CumulativeResourceManager;

class OroEntityExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoad()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(
                [
                    $bundle1->getName() => get_class($bundle1),
                    $bundle2->getName() => get_class($bundle2),
                ]
            );

        $container = new ContainerBuilder();

        $extensionConfig = [];

        $extension = new OroEntityExtension();
        $extension->load($extensionConfig, $container);

        $exclusions = $container->getParameter('oro_entity.exclusions');
        $this->assertEquals(
            [
                ['entity' => 'Test\Entity\Address', 'field' => 'regionText'],
                ['entity' => 'Test\Entity\Product', 'field' => 'code'],
            ],
            $exclusions
        );

        $virtualFields = $container->getParameter('oro_entity.virtual_fields');
        $this->assertEquals(
            [
                'Test\Entity\Address' => [
                    'region_name' => [
                        'query' => [
                            'select' => [
                                'expr'        => 'COALESCE(entity.regionText, region.name)',
                                'return_type' => 'string'
                            ],
                            'join'   => [
                                'left' => [
                                    ['join' => 'entity.region', 'alias' => 'region']
                                ]
                            ]
                        ]
                    ]
                ],
                'Test\Entity\Product' => [
                    'groups' => [
                        'query' => [
                            'select' => [
                                'expr'         => 'target.name',
                                'return_type'  => 'enum',
                                'filter_by_id' => true,
                                'label'        => 'test.product.groups.label',
                            ],
                            'join'   => [
                                'left' => [
                                    ['join' => 'entity.groups', 'alias' => 'target']
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            $virtualFields
        );

        $entityAliases = $container->getParameter('oro_entity.entity_aliases');
        $this->assertEquals(
            [
                'Test\Entity\Product' => [
                    'alias'        => 'testproduct',
                    'plural_alias' => 'testproducts'
                ]
            ],
            $entityAliases
        );

        $entityAliasExclusions = $container->getParameter('oro_entity.entity_alias_exclusions');
        $this->assertEquals(
            [
                'Test\Entity\Address'
            ],
            $entityAliasExclusions
        );

        $textRepresentationTypes = $container->getParameter('oro_entity.entity_name_formats');
        $this->assertEquals(
            [
                'long' => [
                    'fallback' => 'short'
                ],
                'short' => [
                    'fallback' => null
                ],
                'html' => [
                    'fallback' => 'long'
                ]
            ],
            $textRepresentationTypes
        );
    }
}
