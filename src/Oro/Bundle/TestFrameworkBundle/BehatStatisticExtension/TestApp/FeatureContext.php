<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\TestApp;

use Behat\Behat\Context\Context;

/**
 * This context for testing BehatStatisticExtension
 * Never use it in UI tests
 */
final class FeatureContext implements Context
{
    /**
     * @Given I wait for :number seconds
     */
    public function iWaitForSeconds($number)
    {
        sleep($number);
    }
}
