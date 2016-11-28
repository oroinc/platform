<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class SidebarConfigMenu extends Element
{
    public function clickLink($locator)
    {
        $this->find('css', 'a[data-action="accordion:expand-all"]')->click();
        $link = $this->findLink($locator);
        $link->waitFor(1500, function (NodeElement $link) {
            return $link->isVisible();
        });
        $link->click();
    }
}
