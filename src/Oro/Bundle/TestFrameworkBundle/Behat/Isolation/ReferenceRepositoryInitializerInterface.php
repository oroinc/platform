<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

/**
 * Interface for the implementation of the reference initializer.
 */
interface ReferenceRepositoryInitializerInterface
{
    /**
     * Adds references into `referenceRepository` for objects that already in database, usually persisted after install
     *
     * @param Registry $doctrine
     * @param Collection $referenceRepository
     * @return void
     */
    public function init(Registry $doctrine, Collection $referenceRepository);
}
