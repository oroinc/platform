<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadSegmentTypes extends AbstractFixture
{
    /**
     * Load available segment types
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $types = ['static', 'dynamic'];

        foreach ($types as $typeCode) {
            $type = new SegmentType($typeCode);
            $type->setLabel('oro.segment.type.' . $typeCode);

            $manager->persist($type);
        }

        $manager->flush();
    }
}
