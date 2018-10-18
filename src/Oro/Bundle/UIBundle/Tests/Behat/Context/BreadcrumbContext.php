<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class BreadcrumbContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given /^(?:|I )follow "([^"]*)" within page title$/
     *
     * @param string $link
     */
    public function iFollowLinkWithinPageTitle($link)
    {
        $pageTitle = $this->elementFactory->createElement('Page Title');
        $pageTitle->clickOrPress($link);
    }

    /**
     * Checks, that breadcrumbs contain specified text
     * Example: Given I should see "System/ User Management/ Users" in breadcrumbs
     * Example: Given I should see "Customers / Accounts" in breadcrumbs
     *
     * @Given /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)" in breadcrumbs$/
     */
    public function iShouldSeeTextInBreadcrumbs($text)
    {
        $breadcrumbContainer = $this->elementFactory->createElement('Breadcrumb Container');
        $actualText = $breadcrumbContainer->getText();
        $normalizedText = preg_replace('/\s?\//', '', $text);

        self::assertTrue(
            strcasecmp($actualText, $normalizedText) === 0,
            sprintf('Text "%s" does not match to actual breadcrumbs "%s"', $text, $actualText)
        );
    }
}
