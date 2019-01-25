<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ActivityListBundle\Tests\Behat\Element\ActivityList;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SidebarConfigMenu;
use Oro\Bundle\FormBundle\Tests\Behat\Element\AllowedColorsMapping;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroSimpleColorPickerField;
use Oro\Bundle\SalesBundle\Tests\Behat\Element\QuotesGrid;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements
    KernelAwareContext,
    FixtureLoaderAwareInterface,
    OroPageObjectAware
{
    use KernelDictionary, FixtureLoaderDictionary, PageObjectDictionary, AllowedColorsMapping;

    /**
     * Follow link on sidebar in configuration menu
     *
     * Example: Given I follow "System configuration/General setup/Language settings" on configuration sidebar
     *
     * @When /^(?:|I )follow "(?P<path>[^"]*)" on configuration sidebar$/
     */
    public function followLinkOnConfigurationSidebar($path)
    {
        /** @var SidebarConfigMenu $sidebarConfigMenu */
        $sidebarConfigMenu = $this->createElement('SidebarConfigMenu');
        $sidebarConfigMenu->openNestedMenu($path);
    }

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * Asserts that grid has sticky (css:fixed) header
     *
     * @Then /^I see that grid has scrollable header$/
     */
    public function iSeeThatGridHasScrollableHeader()
    {
        self::assertTrue(
            $this->oroMainContext->isNodeVisible('.floatThead.floatThead-fixed'),
            'Grid header have no fixed classes'
        );
    }

    /**
     * Asserts that grid has no sticky header
     *
     * @Then /^I see that grid header is sticky$/
     */
    public function iSeeThatGridHeaderIsSticky()
    {
        self::assertFalse(
            $this->oroMainContext->isNodeVisible('.floatThead.floatThead-fixed'),
            'Grid header still have fixed classes'
        );
    }

    /**
     * Asserts that <Element> items are sorted in provided order
     *
     * Example: Then Activity List must be sorted ascending by updated date
     *
     * @Given /^(?P<name>(?:[\w\s]+)) must be sorted (?P<order>(?:ascending|descending)) by updated date$/
     */
    public function activityListMustBeSortedBy($name, $order)
    {
        /** @var ActivityList $list */
        $list = $this->elementFactory->createElement($name);

        if (!($list instanceof QuotesGrid) && !($list instanceof ActivityList)) {
            self::fail('Methods for retrieving updated date not found in provided element');
        }

        $actual = [];
        foreach ($list->getItems() as $item) {
            if ($item instanceof TableRow) {
                $actual[] = $item->getCellValue('Updated At');
            } else {
                $actual[] = $item->getCreatedAtDate();
            }
        }

        $expected = $actual;
        $order = $order == 'ascending' ? SORT_ASC : SORT_DESC;

        array_multisort($expected, $order);

        self::assertEquals($expected, $actual, "Failed asserting that activity list sorted $order");
    }

    /**
     * Asserts records in activity list with provided table one by one
     * Example: Then I see following records in activity list with provided order:
     *             | Merry christmas |
     *             | Happy new year  |
     *             | Call with Jenny |
     *
     * @Then /^I see following records in activity list with provided order:$/
     */
    public function iSeeFollowingRecordsWithOrder(TableNode $table)
    {
        /** @var ActivityList $list */
        $list = $this->elementFactory->createElement('Activity List');

        foreach ($list->getItems() as $key => $item) {
            // break cycle when all provided items checked
            if (count($table->getRows()) <= $key) {
                break;
            }
            self::assertEquals($table->getRow($key)[0], $item->getTitle());
        }
    }

    /**
     * Asserts visibility of provided sidebar
     *
     * Example: Then right sidebar is out of sight
     *          Then left sidebar is visible
     *
     * @Given /^(left|right) sidebar is (visible|out of sight)$/
     */
    public function leftSidebarIsVisible($position, $visibility)
    {
        $sidebarPanel = $this->getPage()->findVisible('css', "div[id^='sidebar-$position']");

        if ($visibility == 'visible') {
            self::assertNotNull($sidebarPanel, "Failed asserting that $position sidebar is visible");
        } else {
            self::assertNull($sidebarPanel, "Failed asserting that $position sidebar is invisible");
        }
    }

    /**
     * Asserts provided color array with available blocks on page, one by one element
     *
     * If provided color count less than existing on form - will be
     * checked first N elements begins from start
     *
     * Example:  Then I should see following available "Event Form" colors:
     *             | Apple green, Cornflower Blue, Mercury |
     *
     *           Then I should see following available "TaxonomyForm" colors:
     *             | Cornflower Blue, Mercury, Melrose, Mauve |
     *
     * @see AllowedColorsMapping for full list of available color names
     *
     * @Then /^(?:|I )should see following available "(.+)" colors:$/
     */
    public function iShouldSeeFollowingAvailableColors($target, TableNode $table)
    {
        if (!$this->elementFactory->hasElement($target)) {
            throw new \InvalidArgumentException(sprintf('Could not find element with "%s" name', $target));
        }

        $form = $this->elementFactory->createElement($target);
        self::assertNotNull($form->isIsset(), sprintf('Element "%s" not found', $target));

        /** @var OroSimpleColorPickerField $colorPicker */
        $colorPicker = $form->getElement('Simple Color Picker Field');
        self::assertNotNull($colorPicker->isIsset(), sprintf('"Color Picker" not found on "%s" element', $target));
        $availableColors = $colorPicker->getAvailableColors();

        // parse provided color list to an array
        $expectedNames = $table->getRow(0);
        $expectedName = explode(',', reset($expectedNames));
        $expectedNames = array_map('trim', $expectedName);

        foreach ($expectedNames as $expectedName) {
            $currentActual = array_shift($availableColors);
            $currentExpected = $this->getHexByColorName($expectedName);
            self::assertEquals(
                $currentExpected,
                $currentActual,
                "Provided ($currentExpected) and found ($currentActual) on page colors are different"
            );
        }
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

        $isIntegrationVisibleExpectation = empty(trim($negotiation));
        foreach ($table->getRows() as list($row)) {
            if ($isIntegrationVisibleExpectation) {
                self::assertContains($row, $actual, "Integration with name $row not found");
            } else {
                self::assertNotContains($row, $actual, "Integration $row still exists");
            }
        }
    }

    /**
     * Expand all items on sidebar in configuration menu
     *
     * Example: Then I expand all on configuration sidebar
     *
     * @When /^(?:|I )expand all on configuration sidebar$/
     */
    public function expandAllOnConfigurationSidebar()
    {
        /** @var SidebarConfigMenu $sidebarConfigMenu */
        $sidebarConfigMenu = $this->createElement('SidebarConfigMenu');
        $sidebarConfigMenu->expandAll();
    }

    /**
     * Collapse all items on sidebar in configuration menu
     *
     * Example: Then I collapse all on configuration sidebar
     *
     * @When /^(?:|I )collapse all on configuration sidebar$/
     */
    public function collapseAllOnConfigurationSidebar()
    {
        /** @var SidebarConfigMenu $sidebarConfigMenu */
        $sidebarConfigMenu = $this->createElement('SidebarConfigMenu');
        $sidebarConfigMenu->collapseAll();
    }

    /**
     * Enables (set to true) certain config options
     * Used instead of manual walking on configuration like "System/ Configuration"
     *
     * Example: I enable configuration options:
     *      | oro_config.setting1 |
     *      | oro_config.setting2 |
     *
     * @Given /^I enable configuration options:$/
     * @param TableNode $table
     */
    public function enableConfigOptions(TableNode $table): void
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        foreach ($table->getRows() as $row) {
            $configManager->set($row[0], true);
        }

        $configManager->flush();
    }
}
