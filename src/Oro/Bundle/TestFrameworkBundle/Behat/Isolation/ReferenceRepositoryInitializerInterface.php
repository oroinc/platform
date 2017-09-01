<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;

interface ReferenceRepositoryInitializerInterface
{
    /**
     * Adds references into `referenceRepository` for objects that already in database, usually persisted after install
     *
     * @param Registry $doctrine
     * @param AliceCollection $referenceRepository
     * @return void
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository);
}
