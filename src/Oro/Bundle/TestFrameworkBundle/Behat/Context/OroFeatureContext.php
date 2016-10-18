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
        $this->getDriver()->waitForAjax($time);
    }

    /**
     * @return OroSelenium2Driver
     */
    protected function getDriver()
    {
        return $this->getSession()->getDriver();
    }
}
