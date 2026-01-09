<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

/**
 * Provides functionality for managing a collection of health checkers.
 *
 * This trait implements the {@see HealthCheckerAwareInterface}, allowing classes to register
 * and maintain health checkers that validate various aspects of the test environment.
 */
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
