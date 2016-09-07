<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;

class OroFeatureContext extends RawMinkContext
{
    use AssertTrait;

    /**
     * @param int $time
     */
    public function waitForAjax($time = 60000)
    {
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $driver->waitForAjax($time);
    }
}
