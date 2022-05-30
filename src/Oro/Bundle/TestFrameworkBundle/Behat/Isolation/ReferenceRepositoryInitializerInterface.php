<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

/**
 * Interface for the implementation of the reference initializer.
 */
interface ReferenceRepositoryInitializerInterface
{
    /**
     * Adds references into `referenceRepository` for objects that already in database, usually persisted after install
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void;
}
