<?php

namespace Oro\Bundle\ConfigBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
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
}
