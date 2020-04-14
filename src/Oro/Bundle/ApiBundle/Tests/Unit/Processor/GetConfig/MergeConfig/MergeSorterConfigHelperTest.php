<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeSorterConfigHelper;

class MergeSorterConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var MergeSorterConfigHelper */
    private $mergeSorterConfigHelper;

    protected function setUp(): void
    {
        $this->mergeSorterConfigHelper = new MergeSorterConfigHelper();
    }

    public function testMergeEmptySorterConfig()
    {
        $config = [];
        $sorterConfig = [];

        self::assertEquals(
            [
                'sorters' => []
            ],
            $this->mergeSorterConfigHelper->mergeSortersConfig($config, $sorterConfig)
        );
    }

    public function testMergeSorterConfigWithExclusionPolicyEqualsToAll()
    {
        $config = [
            'sorters' => [
                'fields' => [
                    'sorter1' => [
                        'property_path' => 'field1'
                    ],
                    'sorter2' => [
                        'property_path' => 'field2'
                    ]
                ]
            ]
        ];
        $sorterConfig = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'sorter1' => [
                    'property_path' => 'sorterField1'
                ],
                'sorter3' => [
                    'property_path' => 'sorterField3'
                ]
            ]
        ];

        self::assertEquals(
            [
                'sorters' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'sorter1' => [
                            'property_path' => 'sorterField1'
                        ],
                        'sorter3' => [
                            'property_path' => 'sorterField3'
                        ]
                    ]
                ]
            ],
            $this->mergeSorterConfigHelper->mergeSortersConfig($config, $sorterConfig)
        );
    }

    public function testMergeSorterConfigWithoutExclusionPolicy()
    {
        $config = [
            'sorters' => [
                'fields' => [
                    'sorter1' => [
                        'property_path' => 'field1'
                    ],
                    'sorter2' => [
                        'property_path' => 'field2'
                    ]
                ]
            ]
        ];
        $sorterConfig = [
            'fields' => [
                'sorter1' => [
                    'property_path' => 'sorterField1'
                ],
                'sorter3' => [
                    'property_path' => 'sorterField3'
                ]
            ]
        ];

        self::assertEquals(
            [
                'sorters' => [
                    'fields' => [
                        'sorter1' => [
                            'property_path' => 'sorterField1'
                        ],
                        'sorter2' => [
                            'property_path' => 'field2'
                        ],
                        'sorter3' => [
                            'property_path' => 'sorterField3'
                        ]
                    ]
                ]
            ],
            $this->mergeSorterConfigHelper->mergeSortersConfig($config, $sorterConfig)
        );
    }

    public function testMergeSorterConfigWhenConfigDoesNotHaveFieldsSection()
    {
        $config = [
            'sorters' => [
            ]
        ];
        $sorterConfig = [
            'fields' => [
                'sorter1' => null
            ]
        ];

        self::assertEquals(
            [
                'sorters' => [
                    'fields' => [
                        'sorter1' => null
                    ]
                ]
            ],
            $this->mergeSorterConfigHelper->mergeSortersConfig($config, $sorterConfig)
        );
    }
}
