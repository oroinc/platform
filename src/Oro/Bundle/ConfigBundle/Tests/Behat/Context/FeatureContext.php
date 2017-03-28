<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SidebarConfigMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Click link on sidebar in configuration menu
     *
     * Example: Given I click "Inventory" on configuration sidebar
     *
     * @When /^(?:|I )click "(?P<link>(?:[^"]|\\")*)" on configuration sidebar$/
     */
    public function clickLinkOnConfigurationSidebar($link)
    {
        $sidebarConfigMenu = $this->getPage()->find('css', 'div.system-configuration-container div.left-panel');
        $sidebarConfigMenu->clickLink($link);
    }

    /**
     * Assert that following links exists on integration sidebar
     *
     * @Given /^(?:|I )should(?P<negotiation>(\s| not ))see following integrations:$/
     */
    public function iShouldOrNotSeeFollowingIntegrations($negotiation, TableNode $table)
    {
        /** @var SidebarConfigMenu $menu */
        $menu = $this->elementFactory->createElement('SidebarConfigMenu');
        $integrationElements = $menu->getIntegrations();

        self::assertNotEmpty($integrationElements);

        $actual = array_map(function (NodeElement $element) {
            return trim(strip_tags($element->getHtml()));
        }, $integrationElements);

        $isIntegrationVisibleExpectation = !empty(trim($negotiation));
        foreach ($table->getRows() as list($row)) {
            if ($isIntegrationVisibleExpectation) {
                self::assertContains($row, $actual, "Integration with name $row not found");
            } else {
                self::assertNotContains($row, $actual, "Integration $row still exists");
            }
        }
    }
}
