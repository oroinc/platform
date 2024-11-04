<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;

class LoadSegmentData extends AbstractLoadSegmentData
{
    public const SEGMENT_DYNAMIC = 'segment_dynamic';
    public const SEGMENT_DYNAMIC_WITH_FILTER = 'segment_dynamic_with_filter';
    public const SEGMENT_STATIC = 'segment_static';
    public const SEGMENT_STATIC_WITH_FILTER_AND_SORTING = 'segment_static_with_filter_and_sorting';
    public const SEGMENT_STATIC_WITH_SEGMENT_FILTER = 'segment_static_with_segment_filter';
    public const SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS = 'segment_dynamic_with_duplicated_segment_filters';
    public const SEGMENT_DYNAMIC_WITH_FILTER1 = 'segment_dynamic_with_filter1';
    public const SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER = 'segment_dynamic_with_filter2_and_segment_filter';

    private static array $segments = [
        self::SEGMENT_DYNAMIC => [
            'name' => 'Dynamic Segment',
            'description' => 'Dynamic Segment Description',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'Label',
                        'name' => 'id',
                        'sorting' => ''
                    ]
                ],
                'filters' => []
            ]
        ],
        self::SEGMENT_DYNAMIC_WITH_FILTER => [
            'name' => 'Dynamic Segment with Filter',
            'description' => 'Dynamic Segment Description',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'Label',
                        'name' => 'name',
                        'sorting' => 'DESC'
                    ]
                ],
                'filters' => [
                    [
                        'columnName' => 'name',
                        'criterion' => [
                            'filter' => 'string',
                            'data' => [
                                'value' => 'Some not existing name',
                                'type' => TextFilterType::TYPE_CONTAINS,
                            ]
                        ]
                    ]
                ]
            ]
        ],
        self::SEGMENT_STATIC => [
            'name' => 'Static Segment',
            'description' => 'Static Segment Description',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_STATIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'Label',
                        'name' => 'id',
                        'sorting' => ''
                    ]
                ],
                'filters' => []
            ]
        ],
        self::SEGMENT_STATIC_WITH_FILTER_AND_SORTING => [
            'name' => 'Static Segment with Filter',
            'description' => 'Static Segment Description',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_STATIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'Label',
                        'name' => 'name',
                        'sorting' => 'DESC'
                    ]
                ],
                'filters' => [
                    [
                        'columnName' => 'name',
                        'criterion' => [
                            'filter' => 'string',
                            'data' => [
                                'value' => '0',
                                'type' => TextFilterType::TYPE_CONTAINS,
                            ]
                        ]
                    ]
                ]
            ]
        ],
        self::SEGMENT_STATIC_WITH_SEGMENT_FILTER => [
            'name' => 'Static Segment with Segment Filter applied',
            'description' => 'Static Segment Description',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_STATIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'Label',
                        'name' => 'name',
                        'sorting' => 'DESC'
                    ]
                ],
                'filters' => [
                    [
                        'columnName' => 'id',
                        'criteria' => 'condition-segment',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'value' => self::SEGMENT_STATIC, //Will be set to static segment id
                                'type' => null,
                            ]
                        ]
                    ]
                ]
            ]
        ],
        self::SEGMENT_DYNAMIC_WITH_FILTER1 => [
            'name' => 'Entity id > 0',
            'description' => 'Entity id > 0',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'filters' => [
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 0,
                                'type' => NumberFilterType::TYPE_GREATER_THAN,
                            ],
                        ],
                    ],
                ],
                'columns' => [
                    [
                        'name' => 'id',
                        'label' => 'Id',
                        'func' => null,
                        'sorting' => '',
                    ],
                ]
            ]
        ],
        self::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER => [
            'name' => 'Entity id > 1',
            'description' => 'Entity id > 1',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'filters' => [
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 1,
                                'type' => NumberFilterType::TYPE_GREATER_THAN,
                            ],
                        ],
                    ],
                    'AND',
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'type' => null,
                                'value' => self::SEGMENT_DYNAMIC_WITH_FILTER1,
                            ],
                        ],
                        'criteria' => 'condition-segment',
                    ],
                ],
                'columns' => [
                    [
                        'name' => 'id',
                        'label' => 'Id',
                        'func' => null,
                        'sorting' => '',
                    ],
                ]
            ]
        ],
        self::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS => [
            'name' => 'Dynamic Segment with duplicate segment Filter',
            'description' => 'Dynamic Segment with duplicate segment Filter',
            'entity' => WorkflowAwareEntity::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'filters' => [
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 2,
                                'type' => NumberFilterType::TYPE_GREATER_THAN,
                            ],
                        ],
                    ],
                    'AND',
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'type' => null,
                                'value' => self::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER,
                            ],
                        ],
                        'criteria' => 'condition-segment',
                    ],
                    'AND',
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'type' => null,
                                'value' => self::SEGMENT_DYNAMIC_WITH_FILTER1,
                            ],
                        ],
                        'criteria' => 'condition-segment',
                    ],
                    'AND',
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'type' => null,
                                'value' => self::SEGMENT_DYNAMIC_WITH_FILTER1,
                            ],
                        ],
                        'criteria' => 'condition-segment',
                    ],
                    'AND',
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'type' => null,
                                'value' => self::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER,
                            ],
                        ],
                        'criteria' => 'condition-segment',
                    ],
                    'AND',
                    [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'type' => null,
                                'value' => self::SEGMENT_DYNAMIC_WITH_FILTER1,
                            ],
                        ],
                        'criteria' => 'condition-segment',
                    ],
                ],
                'columns' => [
                    [
                        'name' => 'id',
                        'label' => 'Id',
                        'func' => null,
                        'sorting' => '',
                    ],
                ],
            ]
        ]
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);
        $this->applySegmentFilterToDefinition($manager);
    }

    #[\Override]
    protected function getSegmentsData(): array
    {
        return self::$segments;
    }

    private function applySegmentFilterToDefinition(ObjectManager $manager): void
    {
        foreach (self::$segments as $reference => $data) {
            $segment = $this->getReference($reference);
            $definition = $data['definition'];
            foreach ($definition['filters'] as &$filter) {
                if (!is_array($filter)) {
                    continue;
                }
                if (empty($filter['criteria']) || $filter['criteria'] !== 'condition-segment') {
                    continue;
                }

                $criterionValue = $this->getReference($filter['criterion']['data']['value'])->getId();
                $filter['criterion']['data']['value'] = $criterionValue;
            }
            unset($filter);

            $segment->setDefinition(QueryDefinitionUtil::encodeDefinition($definition));
        }
        $manager->flush();
    }
}
