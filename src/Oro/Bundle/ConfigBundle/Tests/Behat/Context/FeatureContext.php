<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ActivityListBundle\Tests\Behat\Element\ActivityList;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements
    KernelAwareContext,
    FixtureLoaderAwareInterface,
    OroPageObjectAware
{
    use KernelDictionary, FixtureLoaderDictionary, PageObjectDictionary;

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
     * Alias for setting parameters on configuration forms
     * Example:   When I set configuration to:
     *              | Minimal password length      | 10   |
     *              | Require a number             | true |
     *
     * @Then /^(?:|I )set configuration to:$/
     */
    public function iSetConfiguration(TableNode $table)
    {
        $this->oroMainContext->iFillFormWith($table, 'SystemConfigForm');
    }

    /**
     * Alias to assert existing WYSIWYG editor on current page
     *
     * @Given /^I should see WYSIWYG editor$/
     */
    public function iShouldSeeWYSIWYGEditor()
    {
        self::assertTrue(
            $this->oroMainContext->elementIsVisible(
                '[data-bound-view="oroform/js/app/views/wysiwig-editor/wysiwyg-dialog-view"] iframe'
            ),
            'WYSIWYG editor not found on current page'
        );
    }

    /**
     * Alias to assert that WYSIWYG editor not exist on current page
     *
     * @Given /^I should not see WYSIWYG editor$/
     */
    public function iShouldNotSeeWYSIWYGEditor()
    {
        self::assertFalse(
            $this->oroMainContext->elementIsVisible(
                '[data-bound-view="oroform/js/app/views/wysiwig-editor/wysiwyg-dialog-view"] iframe'
            ),
            'WYSIWYG editor still exists on page'
        );
    }

    /**
     * Alias for asserting that recent emails block is visible on navbar
     *
     * @Then /^recent emails block should not be visible$/
     */
    public function recentEmailsBlockShouldNotBeVisible()
    {
        self::assertFalse(
            $this->oroMainContext->elementIsVisible('.email-notification-menu'),
            'Recent emails block still visible'
        );
    }

    /**
     * Alias for asserting that recent emails block not visible or not exist
     *
     * @Then /^recent emails block must be visible$/
     */
    public function recentEmailsBlockMustBeVisible()
    {
        self::assertTrue(
            $this->oroMainContext->elementIsVisible('.email-notification-menu'),
            'Recent emails block not found on page'
        );
    }

    /**
     * Asserts per page value on current page with provided amount
     *
     * @Then /^per page amount must be (\d+)$/
     */
    public function perPageAmountMustBe($expectedAmount)
    {
        $perPage = $this->getPage()->find('css', '.grid-toolbar button[data-toggle="dropdown"]');

        self::assertNotNull($perPage, 'Grid per page control elements not found on current page');
        self::assertEquals($expectedAmount, $perPage->getText());
    }

    /**
     * Asserts that grid has sticky (css:fixed) header
     *
     * @Then /^I see that grid has scrollable header$/
     */
    public function iSeeThatGridHasScrollableHeader()
    {
        self::assertTrue(
            $this->oroMainContext->elementIsVisible('.floatThead.floatThead-fixed'),
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
            $this->oroMainContext->elementIsVisible('.floatThead.floatThead-fixed'),
            'Grid header still have fixed classes'
        );
    }

    /**
     * Asserts that pagination controls exists and visible on current page (usually entity view)
     *
     * @Then /^I should see entity pagination controls$/
     */
    public function iShouldSeeEntityPaginationControls()
    {
        self::assertTrue(
            $this->oroMainContext->elementIsVisible('#entity-pagination'),
            'No entity pagination control found on current page'
        );
    }

    /**
     * Asserts that page has no visible pagination controls (usually entity view page)
     *
     * @Then /^I should see no pagination controls$/
     */
    public function iShouldSeeNoPaginationControls()
    {
        self::assertFalse(
            $this->oroMainContext->elementIsVisible('#entity-pagination'),
            'Entity pagination control still appearing on current page'
        );
    }

    /**
     * Asserts that activity list items are be sorted in provided order
     *
     * @Given /^activity list must be sorted (ascending|descending) by updated date$/
     */
    public function activityListMustBeSortedBy($order)
    {
        /** @var ActivityList $list */
        $list = $this->elementFactory->createElement('ActivityList');

        $actual = [];
        foreach ($list->getItems() as $item) {
            $actual[] = $item->getCreatedAtDate();
        }

        $expected = $actual;
        $order = $order == 'ascending' ? SORT_ASC : SORT_DESC;

        array_multisort($expected, $order);

        self::assertEquals($expected, $actual, "Failed asserting that activity list sorted $order");
    }

    /**
     * Asserts records in activity list with provided table one by one
     *
     * @Then /^I see following records in activity list with provided order:$/
     */
    public function iSeeFollowingRecordsWithOrder(TableNode $table)
    {
        /** @var ActivityList $list */
        $list = $this->elementFactory->createElement('ActivityList');

        foreach ($list->getItems() as $key => $item) {
            if (count($table->getRows()) >= $key) {
                break;
            }
            self::assertEquals($table->getRow($key)[0], $item->getTitle());
        }
    }

    /**
     * Asserts visibility of provided sidebar
     *
     * Example: Then right sidebar is out of sight
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
}
