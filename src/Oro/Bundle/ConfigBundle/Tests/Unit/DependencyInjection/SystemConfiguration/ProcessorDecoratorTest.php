<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection\SystemConfiguration;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Symfony\Component\Config\Definition\Processor;

class ProcessorDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorDecorator */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new ProcessorDecorator(new Processor(), []);
    }

    protected function tearDown()
    {
        unset($this->processor);
    }

    /**
     * @dataProvider mergeDataProvider
     *
     * @param array $startData
     * @param array $newData
     * @param array $expectedResult
     */
    public function testMerge($startData, $newData, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->processor->merge($startData, $newData));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mergeDataProvider()
    {
        return [
            'merge tree test'           => [
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::TREE_ROOT => ['group1' => ['group2' => ['field']]],
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::TREE_ROOT => ['group1' => ['group2' => ['field2']]],
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::TREE_ROOT => ['group1' => ['group2' => ['field', 'field2']]],
                    ]
                ],
            ],
            'merge fields test'         => [
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::FIELDS_ROOT => [
                            'someFieldName' => [
                                'label'   => 'testLabel1',
                                'options' => []
                            ]
                        ],
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::FIELDS_ROOT => [
                            'someFieldName' => [
                                'label' => 'overrideLabel',
                            ],
                            'newField'      => [
                                'label'   => 'testLabel2',
                                'options' => []
                            ]
                        ],
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::FIELDS_ROOT => [
                            'someFieldName' => [
                                'label'   => 'overrideLabel',
                                'options' => []
                            ],
                            'newField'      => [
                                'label'   => 'testLabel2',
                                'options' => []
                            ]
                        ],
                    ]
                ],
            ],
            'merge group scalar option' => [
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                            ],
                            'group2' => [
                                'icon' => 'icon1'
                            ],
                            'group3' => [
                                'icon' => 'icon1'
                            ],
                            'group4' => [
                                'icon' => 'icon1'
                            ],
                        ]
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                                'icon' => 'icon2'
                            ],
                            'group2' => [
                            ],
                            'group3' => [
                                'icon' => 'icon2'
                            ],
                            'group5' => [
                                'icon' => 'icon2'
                            ],
                        ]
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                                'icon' => 'icon2'
                            ],
                            'group2' => [
                                'icon' => 'icon1'
                            ],
                            'group3' => [
                                'icon' => 'icon2'
                            ],
                            'group4' => [
                                'icon' => 'icon1'
                            ],
                            'group5' => [
                                'icon' => 'icon2'
                            ],
                        ]
                    ]
                ],
            ],
            'merge group configurators' => [
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                            ],
                            'group2' => [
                                'configurator' => 'Test\Class1::method'
                            ],
                            'group3' => [
                                'configurator' => 'Test\Class1::method'
                            ],
                            'group4' => [
                                'configurator' => ['Test\Class1::method']
                            ],
                            'group5' => [
                                'configurator' => 'Test\Class1::method'
                            ],
                        ]
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                                'configurator' => 'Test\Class2::method'
                            ],
                            'group2' => [
                                'configurator' => 'Test\Class2::method'
                            ],
                            'group3' => [
                                'configurator' => ['Test\Class2::method']
                            ],
                            'group4' => [
                                'configurator' => ['Test\Class2::method']
                            ],
                            'group6' => [
                                'configurator' => 'Test\Class2::method'
                            ],
                        ]
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                                'configurator' => 'Test\Class2::method'
                            ],
                            'group2' => [
                                'configurator' => ['Test\Class1::method', 'Test\Class2::method']
                            ],
                            'group3' => [
                                'configurator' => ['Test\Class1::method', 'Test\Class2::method']
                            ],
                            'group4' => [
                                'configurator' => ['Test\Class1::method', 'Test\Class2::method']
                            ],
                            'group5' => [
                                'configurator' => 'Test\Class1::method'
                            ],
                            'group6' => [
                                'configurator' => 'Test\Class2::method'
                            ],
                        ]
                    ]
                ],
            ],
            'merge group handlers'      => [
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                            ],
                            'group2' => [
                                'handler' => 'Test\Class1::method'
                            ],
                            'group3' => [
                                'handler' => 'Test\Class1::method'
                            ],
                            'group4' => [
                                'handler' => ['Test\Class1::method']
                            ],
                            'group5' => [
                                'handler' => 'Test\Class1::method'
                            ],
                        ]
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                                'handler' => 'Test\Class2::method'
                            ],
                            'group2' => [
                                'handler' => 'Test\Class2::method'
                            ],
                            'group3' => [
                                'handler' => ['Test\Class2::method']
                            ],
                            'group4' => [
                                'handler' => ['Test\Class2::method']
                            ],
                            'group6' => [
                                'handler' => 'Test\Class2::method'
                            ],
                        ]
                    ]
                ],
                [
                    ProcessorDecorator::ROOT => [
                        ProcessorDecorator::GROUPS_NODE => [
                            'group1' => [
                                'handler' => 'Test\Class2::method'
                            ],
                            'group2' => [
                                'handler' => ['Test\Class1::method', 'Test\Class2::method']
                            ],
                            'group3' => [
                                'handler' => ['Test\Class1::method', 'Test\Class2::method']
                            ],
                            'group4' => [
                                'handler' => ['Test\Class1::method', 'Test\Class2::method']
                            ],
                            'group5' => [
                                'handler' => 'Test\Class1::method'
                            ],
                            'group6' => [
                                'handler' => 'Test\Class2::method'
                            ],
                        ]
                    ]
                ],
            ],
        ];
    }
}
