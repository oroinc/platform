<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Click on button or link on left panel in configuration menu
     * Example: Given I click "Edit" on left panel
     * Example: When I click "Save and Close" on left panel
     *
     * @When /^(?:|I )click "(?P<button>(?:[^"]|\\")*)" on left panel$/
     */
    public function pressButtonOnLeftPanel($button)
    {
        $leftPanel = $this->getPage()->find('css', 'div.left-panel');
        $leftPanel->clickLink($button);
    }
}
