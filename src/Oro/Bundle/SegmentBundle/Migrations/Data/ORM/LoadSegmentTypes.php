<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

/**
 * Data fixture that loads available segment types into the database.
 *
 * This fixture creates and persists the standard segment types (dynamic and static)
 * during the data loading phase of application installation or migration. These types
 * define the fundamental segment categories available in the system. Dynamic segments
 * are computed based on criteria, while static segments contain a fixed list of entities.
 * This fixture ensures that the application always has these core segment types available.
 */
class LoadSegmentTypes extends AbstractFixture
{
    /**
     * Load available segment types
     */
    #[\Override]
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
