<?php

namespace Oro\Bundle\HelpBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext
{
    /**
     * @Then the documentation will opened
     */
    public function theDocumentationWillOpened()
    {
        $windowNames = $this->getSession()->getWindowNames();
        $this->getSession()->switchToWindow($windowNames[1]);
        self::assertContains(
            'www.orocrm.com/documentation/index',
            $this->getSession()->getCurrentUrl()
        );
    }
}
