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
            'Community Documentation - OroCRM - Open-Source CRM',
            $this->getSession()->getPage()->find('css', 'title')->getHtml()
        );
    }
}
