<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Configuration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\ConfigurationProvider;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    protected function setUp(): void
    {
        $this->configurationProvider = new ConfigurationProvider(
            $this->getTempFile('QueryDesignerConfigurationProvider'),
            false,
            new Configuration(['string', 'integer', 'number', 'boolean'])
        );
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

        $config = $this->configurationProvider->getConfiguration();
        $this->assertEquals($expectedResult, $config[$sectionName]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configurationDataProvider(): array
    {
        return [
            'filters'    => [
                'filters',
                [
                    'filter1' => [
                        'applicable'     => [
                            ['type' => 'string'],
                            ['type' => 'text']
                        ],
                        'type'           => 'string',
                        'query_type'     => ['all'],
                        'template_theme' => 'embedded'
                    ],
                    'filter2' => [
                        'applicable'     => [
                            ['entity' => 'TestEntity', 'field' => 'TestField']
                        ],
                        'type'           => 'string',
                        'query_type'     => ['all'],
                        'template_theme' => 'embedded'
                    ],
                    'filter3' => [
                        'applicable'     => [
                            ['type' => 'integer']
                        ],
                        'type'           => 'number',
                        'query_type'     => ['all'],
                        'template_theme' => 'embedded'
                    ],
                    'filter4' => [
                        'applicable'     => [
                            ['type' => 'boolean']
                        ],
                        'type'           => 'boolean',
                        'query_type'     => ['type1', 'type2'],
                        'template_theme' => 'embedded'
                    ],
                    'filter5' => [
                        'applicable'     => [
                            ['type' => 'other']
                        ],
                        'type'           => 'string',
                        'query_type'     => ['all'],
                        'template_theme' => 'embedded',
                        'options'        => [
                            'option1' => true,
                            'option2' => 'val2'
                        ],
                        'init_module'    => 'module1'
                    ]
                ]
            ],
            'grouping'   => [
                'grouping',
                [
                    'exclude' => [
                        ['type' => 'text'],
                        ['type' => 'array']
                    ]
                ]
            ],
            'converters' => [
                'converters',
                [
                    'converter1' => [
                        'applicable' => [
                            ['type' => 'string'],
                            ['type' => 'text']
                        ],
                        'functions'  => [
                            [
                                'name'        => 'Func1',
                                'expr'        => 'FUNC1($column)',
                                'return_type' => 'string',
                                'name_label'  => 'oro.query_designer.converters.converter1.Func1.name',
                                'hint_label'  => 'oro.query_designer.converters.converter1.Func1.hint'
                            ],
                            [
                                'name'       => 'Func2',
                                'expr'       => 'FUNC2($column)',
                                'name_label' => 'oro.query_designer.converters.converter1.Func2.name',
                                'hint_label' => 'oro.query_designer.converters.converter1.Func2.hint'
                            ]
                        ],
                        'query_type' => ['type1']
                    ]
                ]
            ],
            'aggregates' => [
                'aggregates',
                [
                    'aggregate1' => [
                        'applicable' => [
                            ['type' => 'integer'],
                            ['type' => 'smallint'],
                            ['type' => 'float']
                        ],
                        'functions'  => [
                            [
                                'name'       => 'Min',
                                'expr'       => 'MIN($column)',
                                'name_label' => 'oro.query_designer.aggregates.aggregate1.Min.name',
                                'hint_label' => 'oro.query_designer.aggregates.aggregate1.Min.hint'
                            ],
                            [
                                'name'       => 'Max',
                                'expr'       => 'MAX($column)',
                                'name_label' => 'oro.query_designer.aggregates.aggregate1.Max.name',
                                'hint_label' => 'oro.query_designer.aggregates.aggregate1.Max.hint'
                            ],
                            [
                                'name'        => 'Count',
                                'expr'        => 'COUNT($column)',
                                'return_type' => 'integer',
                                'name_label'  => 'oro.query_designer.aggregates.aggregate1.Count.name',
                                'hint_label'  => 'oro.query_designer.aggregates.aggregate1.Count.hint'
                            ]
                        ],
                        'query_type' => ['type1']
                    ]
                ]
            ]
        ];
    }
}
