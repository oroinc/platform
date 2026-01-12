<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

/**
 * Defines the contract for classes that can register health checkers.
 *
 * Classes implementing this interface can accept and manage health checkers that validate
 * the test environment (e.g., code style, fixture integrity).
 */
interface HealthCheckerAwareInterface
{
    public function addHealthChecker(HealthCheckerInterface $healthChecker);
}
