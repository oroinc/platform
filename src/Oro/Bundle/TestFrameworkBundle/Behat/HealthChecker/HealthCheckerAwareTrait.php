<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

trait HealthCheckerAwareTrait
{
    /**
     * @var HealthCheckerInterface[]
     */
    protected $healthCheckers = [];

    public function addHealthChecker(HealthCheckerInterface $healthChecker)
    {
        $this->healthCheckers[] = $healthChecker;
    }
}
