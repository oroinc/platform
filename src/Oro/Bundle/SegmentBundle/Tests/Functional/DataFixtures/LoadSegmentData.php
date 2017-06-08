<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;

class LoadSegmentData extends AbstractFixture
{
    const SEGMENT_DYNAMIC = 'segment_dynamic';
    const SEGMENT_DYNAMIC_WITH_FILTER = 'segment_dynamic_with_filter';
    const SEGMENT_STATIC = 'segment_static';
    const SEGMENT_STATIC_WITH_FILTER_AND_SORTING = 'segment_static_with_filter_and_sorting';
    const SEGMENT_STATIC_WITH_SEGMENT_FILTER = 'segment_static_with_segment_filter';

    /** @var array */
    private static $segments = [
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
                'filters' =>[]
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
                'filters' =>[
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
                'filters' =>[]
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
                'filters' =>[
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
                'filters' =>[
                    [
                        'columnName' => 'id',
                        'criteria' => 'condition-segment',
                        'criterion' => [
                            'filter' => 'segment',
                            'data' => [
                                'value' => null, //Will be set to static segment id
                                'type' => null,
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $owner = $organization->getBusinessUnits()->first();

        foreach (self::$segments as $segmentReference => $data) {
            $segmentType = $manager->getRepository(SegmentType::class)->find($data['type']);

            $entity = new Segment();
            $entity->setName($data['name']);
            $entity->setDescription($data['description']);
            $entity->setEntity($data['entity']);
            $entity->setOwner($owner);
            $entity->setType($segmentType);
            $entity->setOrganization($organization);
            $entity->setDefinition(json_encode($data['definition']));

            $this->setReference($segmentReference, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
        $this->applySegmentFilterToDefinition($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    private function applySegmentFilterToDefinition(ObjectManager $manager)
    {
        $staticSegment = $this->getReference(self::SEGMENT_STATIC);
        $staticSegmentWithSegmentFilter = $this->getReference(self::SEGMENT_STATIC_WITH_SEGMENT_FILTER);
        $definition = self::$segments[self::SEGMENT_STATIC_WITH_SEGMENT_FILTER]['definition'];
        $definition['filters'][0]['criterion']['data']['value'] = $staticSegment->getId();
        $staticSegmentWithSegmentFilter->setDefinition(json_encode($definition));
        $manager->flush();
    }
}
