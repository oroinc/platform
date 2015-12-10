<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\NormalizeFilters;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class NormalizeFiltersTest extends ConfigProcessorTestCase
{
    /** @var NormalizeFilters */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();
        $this->processor = new NormalizeFilters();
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($definition, $filters, $expectedFilters)
    {
        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);
        $this->assertEquals($expectedFilters, $this->context->getFilters());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processProvider()
    {
        return [
            'empty'                                                          => [
                'definition'      => [],
                'filters'         => [],
                'expectedFilters' => [],
            ],
            'no child filters'                                               => [
                'definition'      => [
                    'fields' => []
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
            ],
            'child filters'                                                  => [
                'definition'      => [
                    'fields' => [
                        'field2' => [
                            'filters' => [
                                'fields' => [
                                    'field21' => [
                                        'data_type' => 'string',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'         => [
                            'data_type' => 'string',
                        ],
                        'field2.field21' => [
                            'data_type' => 'string',
                        ]
                    ]
                ]
            ],
            'child filters, fields property paths'                           => [
                'definition'      => [
                    'fields' => [
                        'field1' => [
                            'definition' => [
                                'property_path' => 'realField1'
                            ]
                        ],
                        'field2' => [
                            'definition' => [
                                'property_path' => 'realField2',
                                'fields'        => [
                                    'field21' => [
                                        'definition' => [
                                            'property_path' => 'realField21'
                                        ]
                                    ]
                                ]
                            ],
                            'filters'    => [
                                'fields' => [
                                    'field21' => [
                                        'data_type' => 'string',
                                    ]
                                ]
                            ]
                        ],
                        'field3' => [
                            'definition' => [
                                'property_path' => 'realField3',
                                'fields'        => [
                                    'field31' => [
                                        'property_path' => 'realField31',
                                    ],
                                    'field32' => [
                                        'definition' => [
                                            'property_path' => 'realField32',
                                            'fields'        => [
                                                'field321' => [
                                                    'property_path' => 'realField321',
                                                ]
                                            ]
                                        ],
                                        'filters'    => [
                                            'fields' => [
                                                'field321' => [
                                                    'data_type' => 'string',
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'filters'    => [
                                'fields' => [
                                    'field32' => [
                                        'data_type' => 'string',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type' => 'string',
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'                  => [
                            'data_type'     => 'string',
                            'property_path' => 'realField1'
                        ],
                        'field2.field21'          => [
                            'data_type'     => 'string',
                            'property_path' => 'realField2.realField21'
                        ],
                        'field3.field32'          => [
                            'data_type'     => 'string',
                            'property_path' => 'realField3.realField32'
                        ],
                        'field3.field32.field321' => [
                            'data_type'     => 'string',
                            'property_path' => 'realField3.realField32.realField321'
                        ]
                    ]
                ]
            ],
            'child filters, filters property paths should not be overridden' => [
                'definition'      => [
                    'fields' => [
                        'field1' => [
                            'property_path' => 'realField1'
                        ],
                        'field2' => [
                            'definition' => [
                                'property_path' => 'realField2',
                                'fields'        => [
                                    'field21' => [
                                        'property_path' => 'realField21',
                                    ]
                                ]
                            ],
                            'filters'    => [
                                'fields' => [
                                    'field21' => [
                                        'data_type'     => 'string',
                                        'property_path' => 'filterRealField21'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'filters'         => [
                    'fields' => [
                        'field1' => [
                            'data_type'     => 'string',
                            'property_path' => 'filterRealField1'
                        ]
                    ]
                ],
                'expectedFilters' => [
                    'fields' => [
                        'field1'         => [
                            'data_type'     => 'string',
                            'property_path' => 'filterRealField1'
                        ],
                        'field2.field21' => [
                            'data_type'     => 'string',
                            'property_path' => 'realField2.filterRealField21'
                        ]
                    ]
                ]
            ],
        ];
    }
}
