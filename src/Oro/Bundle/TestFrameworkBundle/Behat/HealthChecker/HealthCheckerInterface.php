<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
