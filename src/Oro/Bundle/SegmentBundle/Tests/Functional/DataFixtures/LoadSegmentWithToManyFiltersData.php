<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadSegmentWithToManyFiltersData extends AbstractLoadSegmentData
{
    const SEGMENT_FILTER_NO_GROUP = 'segment_filter_no_group';
    const SEGMENT_FILTER_GROUP = 'segment_filter_group';

    /** @var array */
    protected static $segments = [
        self::SEGMENT_FILTER_NO_GROUP => [
            'name' => 'Segment1',
            'description' => 'Segment1',
            'entity' => Organization::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'columns' => [
                    [
                        'name' => 'name',
                        'label' => 'Name',
                        'sorting' => '',
                        'func' => null
                    ]
                ],
                'filters' => [
                    [
                        'columnName' => 'users+Oro\\Bundle\\UserBundle\\Entity\\User::firstName',
                        'criterion' => [
                            'filter' => 'string',
                            'data' => [
                                'value' => 'fn1',
                                'type' => '3'
                            ]
                        ]
                    ],
                    'AND',
                    [
                        'columnName' => 'users+Oro\\Bundle\\UserBundle\\Entity\\User::lastName',
                        'criterion' => [
                            'filter' => 'string',
                            'data' => [
                                'value' => 'ln1',
                                'type' => '3'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        self::SEGMENT_FILTER_GROUP => [
            'name' => 'Segment2',
            'description' => 'Segment2',
            'entity' => Organization::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'columns' => [
                    [
                        'name' => 'name',
                        'label' => 'Name',
                        'sorting' => '',
                        'func' => null
                    ]
                ],
                'filters' => [
                    [
                        [
                            'columnName' => 'users+Oro\\Bundle\\UserBundle\\Entity\\User::firstName',
                            'criterion' => [
                                'filter' => 'string',
                                'data' => [
                                    'value' => 'fn1',
                                    'type' => '3'
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'columnName' => 'users+Oro\\Bundle\\UserBundle\\Entity\\User::lastName',
                            'criterion' => [
                                'filter' => 'string',
                                'data' => [
                                    'value' => 'ln1',
                                    'type' => '3'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function getSegmentsData(): array
    {
        return self::$segments;
    }
}
