<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads segment types from the database.
 */
class LoadSegmentTypes extends AbstractFixture implements InitialFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $repository = $manager->getRepository(SegmentType::class);
        $this->addReference(
            'segment_dynamic_type',
            $repository->findOneBy(['name' => SegmentType::TYPE_DYNAMIC])
        );
        $this->addReference(
            'segment_static_type',
            $repository->findOneBy(['name' => SegmentType::TYPE_STATIC])
        );
    }
}
