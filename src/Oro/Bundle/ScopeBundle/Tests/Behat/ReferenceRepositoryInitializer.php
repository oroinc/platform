<?php

namespace Oro\Bundle\ScopeBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

/**
 * Create a reference for root category.
 */
class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getRepository(Scope::class);
        $referenceRepository->set('default_scope', $repository->findOneBy([]));
    }
}
