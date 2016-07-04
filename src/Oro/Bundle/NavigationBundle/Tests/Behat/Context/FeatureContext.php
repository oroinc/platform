<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class FeatureContext extends RawMinkContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * @Given uncheck Use Default for :label field
     */
    public function uncheckUseDefaultForField($label)
    {
        $this->createElement('SystemConfigForm')->uncheckUseDefaultCheckbox($label);
    }

    /**
     * @When I save setting
     */
    public function iSaveSetting()
    {
        $this->getSession()->getPage()->pressButton('Save settings');
    }

    /**
     * @Then menu must be on left side
     * @Then menu is on the left side
     */
    public function menuMustBeOnLeftSide()
    {
        \PHPUnit_Framework_Assert::assertFalse($this->createElement('MainMenu')->hasClass('main-menu-top'));
    }

    /**
     * @Then menu must be at top
     * @Then menu is at the top
     */
    public function menuMustBeOnRightSide()
    {
        \PHPUnit_Framework_Assert::assertTrue($this->createElement('MainMenu')->hasClass('main-menu-top'));
    }
}
