<?php

namespace Oro\Bundle\SegmentBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $types = $doctrine->getManagerForClass(SegmentType::class)->getRepository(SegmentType::class)->findAll();
        foreach ($types as $type) {
            $referenceRepository->set(sprintf('segment_%s_type', strtolower($type->getName())), $type);
        }
    }
}
