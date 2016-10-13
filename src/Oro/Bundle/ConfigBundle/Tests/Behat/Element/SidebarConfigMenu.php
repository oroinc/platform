<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class SidebarConfigMenu extends Element
{
    public function clickLink($locator)
    {
        $this->find('css', 'a[data-action="accordion:expand-all"]')->click();
        $this->getDriver()->waitForAjax();
        $this->findLink($locator)->click();
    }
}
