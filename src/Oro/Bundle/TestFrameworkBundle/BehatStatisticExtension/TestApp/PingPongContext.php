<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\TestApp;

use Behat\Behat\Context\Context;

/**
 * Test context for basic ping-pong scenario testing.
 *
 * This context provides simple step definitions for testing the Behat statistic extension
 * with minimal test scenarios, useful for verifying extension functionality.
 */
class PingPongContext implements Context
{
    /**
     * @Given ping
     */
    public function ping()
    {
    }

    /**
     * @Then pong
     */
    public function pong()
    {
    }
}
