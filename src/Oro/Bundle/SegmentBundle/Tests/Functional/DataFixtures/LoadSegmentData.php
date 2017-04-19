<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

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
                                'type' => 1,
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
                                'value' => 'entity',
                                'type' => 1,
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
    }
}
