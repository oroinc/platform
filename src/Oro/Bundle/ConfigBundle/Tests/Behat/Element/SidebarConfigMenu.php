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
        $link->waitFor(60, function (NodeElement $link) {
            return $link->isVisible();
        });
        $link->click();
    }

    /**
     * @return \Behat\Mink\Element\NodeElement[]
     */
    public function getIntegrations()
    {
        return $this->findAll('css', '#config_tab_group_integrations li a');
    }
}
