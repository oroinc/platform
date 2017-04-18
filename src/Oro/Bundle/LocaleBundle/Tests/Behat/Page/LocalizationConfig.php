<?php

namespace Oro\Bundle\LocaleBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class LocalizationConfig extends Page
{

    /**
     * Open page using parameters
     *
     * @param array $parameters
     */
    public function open(array $parameters = [])
    {
        $mainMenu = $this->elementFactory->createElement('MainMenu');
        $mainMenu->openAndClick('System/Configuration');

        $this->waitForAjax();

        $this->elementFactory->getPage()
            ->findVisible('xpath', "//a[@href='/config/system/platform/localization']")
            ->click();
    }
}
