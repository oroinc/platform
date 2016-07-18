<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SystemConfigForm;
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
        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->uncheckUseDefaultCheckbox($label);
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
        expect($this->createElement('MainMenu')->hasClass('main-menu-top'))->toBe(false);
    }

    /**
     * @Then menu must be at top
     * @Then menu is at the top
     */
    public function menuMustBeOnRightSide()
    {
        expect($this->createElement('MainMenu')->hasClass('main-menu-top'))->toBe(true);
    }
}
