<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the contract for health checkers that validate the test environment.
 *
 * Health checkers are event subscribers that monitor test execution and report any issues
 * found (e.g., code style violations, fixture problems). They provide error messages and
 * a name for identification.
 */
interface HealthCheckerInterface extends EventSubscriberInterface
{
    /**
     * @return bool
     */
    public function isFailure();

    /**
     * Return array of strings error messages
     * @return string[]
     */
    public function getErrors();

    /**
     * @return string
     */
    public function getName();
}
