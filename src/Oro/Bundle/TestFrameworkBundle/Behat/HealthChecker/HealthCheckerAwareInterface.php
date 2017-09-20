<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

interface HealthCheckerAwareInterface
{
    /**
     * @param HealthCheckerInterface $healthChecker
     */
    public function addHealthChecker(HealthCheckerInterface $healthChecker);
}
