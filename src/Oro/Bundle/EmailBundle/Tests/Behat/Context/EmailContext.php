<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class EmailContext extends OroFeatureContext
{
    /**
     * @Given /^Email should contains the following "([^"]*)" text$/
     * @param string $text
     */
    public function emailShouldContainsTheFollowingText($text)
    {
        //todo: to be implemented in scope of CRM-7599. Consulted with Serhii Polishchuk
    }
}
