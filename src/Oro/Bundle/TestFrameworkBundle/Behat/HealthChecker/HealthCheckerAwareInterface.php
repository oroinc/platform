<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

interface HealthCheckerAwareInterface
{
    public function addHealthChecker(HealthCheckerInterface $healthChecker);
}
