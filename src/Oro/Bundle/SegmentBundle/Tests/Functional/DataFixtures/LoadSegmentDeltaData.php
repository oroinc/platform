<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadSegmentDeltaData extends AbstractFixture implements DependentFixtureInterface
{
    const SEGMENT = 'collection-segment';

    const SEGMENT_EXISTING = LoadSegmentData::SEGMENT_STATIC;
    const SEGMENT_REMOVED = LoadSegmentData::SEGMENT_DYNAMIC;
    const SEGMENT_ADDED = LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING;

    /**
     * @var array
     */
    const SEGMENT_DEFINITION = [
        'columns' => [
            [
                'func' => null,
                'label' => 'Name',
                'name' => 'name',
                'sorting' => '',
            ],
        ],
        'filters' =>[
            [
                'columnName' => 'name',
                'criterion' => [
                    'filter' => 'string',
                    'data' => [
                        'value' => 'Static Segment',
                        'type' => TextFilterType::TYPE_STARTS_WITH,
                    ],
                ],
            ],
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $segmentType = $manager->getRepository(SegmentType::class)->find(SegmentType::TYPE_STATIC);
        $segment = new Segment();
        $segment->setName(self::SEGMENT);
        $segment->setEntity(Segment::class);
        $segment->setType($segmentType);
        $segment->setDefinition(json_encode(self::SEGMENT_DEFINITION));
        $manager->persist($segment);
        $this->setReference(self::SEGMENT, $segment);

        $segmentSnapshot = new SegmentSnapshot($segment);
        $segmentSnapshot->setIntegerEntityId($this->getReference(self::SEGMENT_EXISTING)->getId());
        $manager->persist($segmentSnapshot);

        $segmentSnapshot2 = new SegmentSnapshot($segment);
        $segmentSnapshot2->setIntegerEntityId($this->getReference(self::SEGMENT_REMOVED)->getId());
        $manager->persist($segmentSnapshot2);

        $manager->flush();
    }
}
