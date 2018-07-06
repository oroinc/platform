<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeFilterConfigHelper;

class MergeFilterConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var MergeFilterConfigHelper */
    private $mergeFilterConfigHelper;

    protected function setUp()
    {
        $this->mergeFilterConfigHelper = new MergeFilterConfigHelper();
    }

    public function testMergeEmptyFilterConfig()
    {
        $config = [];
        $filterConfig = [];

        self::assertEquals(
            [
                'filters' => []
            ],
            $this->mergeFilterConfigHelper->mergeFiltersConfig($config, $filterConfig)
        );
    }

    public function testMergeFilterConfigWithExclusionPolicyEqualsToAll()
    {
        $config = [
            'filters' => [
                'fields' => [
                    'filter1' => [
                        'description' => 'description 1'
                    ],
                    'filter2' => [
                        'description' => 'description 2'
                    ]
                ]
            ]
        ];
        $filterConfig = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'filter1' => [
                    'description' => 'filter description 1'
                ],
                'filter3' => [
                    'description' => 'filter description 3'
                ]
            ]
        ];

        self::assertEquals(
            [
                'filters' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'filter1' => [
                            'description' => 'filter description 1'
                        ],
                        'filter3' => [
                            'description' => 'filter description 3'
                        ]
                    ]
                ]
            ],
            $this->mergeFilterConfigHelper->mergeFiltersConfig($config, $filterConfig)
        );
    }

    public function testMergeFilterConfigWithoutExclusionPolicy()
    {
        $config = [
            'filters' => [
                'fields' => [
                    'filter1' => [
                        'description' => 'description 1'
                    ],
                    'filter2' => [
                        'description' => 'description 2'
                    ]
                ]
            ]
        ];
        $filterConfig = [
            'fields' => [
                'filter1' => [
                    'description' => 'filter description 1'
                ],
                'filter3' => [
                    'description' => 'filter description 3'
                ]
            ]
        ];

        self::assertEquals(
            [
                'filters' => [
                    'fields' => [
                        'filter1' => [
                            'description' => 'filter description 1'
                        ],
                        'filter2' => [
                            'description' => 'description 2'
                        ],
                        'filter3' => [
                            'description' => 'filter description 3'
                        ]
                    ]
                ]
            ],
            $this->mergeFilterConfigHelper->mergeFiltersConfig($config, $filterConfig)
        );
    }

    public function testMergeFilterConfigWhenConfigDoesNotHaveFieldsSection()
    {
        $config = [
            'filters' => [
            ]
        ];
        $filterConfig = [
            'fields' => [
                'filter1' => null
            ]
        ];

        self::assertEquals(
            [
                'filters' => [
                    'fields' => [
                        'filter1' => null
                    ]
                ]
            ],
            $this->mergeFilterConfigHelper->mergeFiltersConfig($config, $filterConfig)
        );
    }
}
