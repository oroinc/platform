<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Configuration;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class EntityConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private EntityConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('EntityConfigurationProvider');

        $this->configurationProvider = new EntityConfigurationProvider($cacheFile, false);
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testGetConfiguration(string $sectionName, array $expectedResult)
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->assertEquals(
            $expectedResult,
            $this->configurationProvider->getConfiguration($sectionName)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configurationDataProvider(): array
    {
        return [
            'exclusions'              => [
                EntityConfiguration::EXCLUSIONS,
                [
                    ['entity' => 'Test\Entity\Address', 'field' => 'regionText'],
                    ['entity' => 'Test\Entity\Product', 'field' => 'code'],
                ]
            ],
            'entity aliases'          => [
                EntityConfiguration::ENTITY_ALIASES,
                [
                    'Test\Entity\Product' => [
                        'alias'        => 'testproduct',
                        'plural_alias' => 'testproducts'
                    ]
                ]
            ],
            'entity alias exclusions' => [
                EntityConfiguration::ENTITY_ALIAS_EXCLUSIONS,
                [
                    'Test\Entity\Address'
                ]
            ],
            'virtual fields'          => [
                EntityConfiguration::VIRTUAL_FIELDS,
                [
                    'Test\Entity\Address' => [
                        'region_name' => [
                            'query' => [
                                'select' => [
                                    'expr'         => 'COALESCE(entity.regionText, region.name)',
                                    'return_type'  => 'string',
                                    'translatable' => true
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
                        'groups'   => [
                            'query' => [
                                'select' => [
                                    'expr'         => 'target.name',
                                    'return_type'  => 'enum',
                                    'filter_by_id' => true,
                                    'label'        => 'test.product.groups.label',
                                    'translatable' => true,
                                ],
                                'join'   => [
                                    'left' => [
                                        ['join' => 'entity.groups', 'alias' => 'target']
                                    ]
                                ]
                            ]
                        ],
                        'category' => [
                            'query' => [
                                'select' => [
                                    'expr'         => 'target.name',
                                    'return_type'  => 'enum',
                                    'filter_by_id' => true,
                                    'label'        => 'test.product.category.label',
                                    'translatable' => true
                                ],
                                'join'   => [
                                    'left' => [
                                        ['join' => 'entity.category', 'alias' => 'target']
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            'virtual relations'       => [
                EntityConfiguration::VIRTUAL_RELATIONS,
                [
                    'Test\Entity\Contact' => [
                        'primaryAddr' => [
                            'relation_type'       => 'oneToOne',
                            'related_entity_name' => 'Test\Entity\ContactAddress',
                            'label'               => 'test.primary_addr.label',
                            'query'               => [
                                'join' => [
                                    'left' => [
                                        [
                                            'join'          => 'entity.addresses',
                                            'alias'         => 'addresses',
                                            'conditionType' => 'WITH',
                                            'condition'     => 'addresses.primary = true'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'entity name formats'     => [
                EntityConfiguration::ENTITY_NAME_FORMATS,
                [
                    'long'  => [
                        'fallback' => 'short'
                    ],
                    'short' => [
                        'fallback' => null
                    ],
                    'html'  => [
                        'fallback' => 'long'
                    ]
                ]
            ]
        ];
    }
}
