<?php

namespace Oro\Bundle\SegmentBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $types = $doctrine->getManagerForClass(SegmentType::class)->getRepository(SegmentType::class)->findAll();
        foreach ($types as $type) {
            $referenceRepository->set(sprintf('segment_%s_type', strtolower($type->getName())), $type);
        }
    }
}
