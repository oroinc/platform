<?php

namespace Oro\Bundle\HelpBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext
{
    /**
     * Example: Then the documentation "www.orocrm.com/documentation/current" will opened
     * @Then /^the documentation "([^"]*)" will opened$/
     *
     * @param string $url
     */
    public function theDocumentationWillOpened($url)
    {
        $windowNames = $this->getSession()->getWindowNames();
        $this->getSession()->switchToWindow(end($windowNames));
        self::assertContains($url, $this->getSession()->getCurrentUrl());
    }
}
