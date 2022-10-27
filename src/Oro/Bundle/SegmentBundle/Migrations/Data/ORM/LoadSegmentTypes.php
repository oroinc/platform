<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadSegmentTypes extends AbstractFixture
{
    /**
     * Load available segment types
     */
    public function load(ObjectManager $manager)
    {
        $types = [SegmentType::TYPE_DYNAMIC, SegmentType::TYPE_STATIC];

        foreach ($types as $typeCode) {
            $type = new SegmentType($typeCode);
            $type->setLabel('oro.segment.type.' . $typeCode);

            $manager->persist($type);
        }

        $manager->flush();
    }
}
