<?php

namespace Oro\Bundle\DashboardBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Example: I should see "Leads list" widget on dashboard
     *
     * @Given /^(?:|I )should see "(?P<widget>[\w\s]+)" widget on dashboard$/
     */
    public function iShouldSeeDashboardWidget($widget): void
    {
        $widget = $this->createElement($widget);
        self::assertTrue($widget->isValid());
    }

    /**
     * Example: I should not see "Leads list" widget on dashboard
     *
     * @Given /^(?:|I )should not see "(?P<widget>[\w\s]+)" widget on dashboard$/
     */
    public function iShouldNotSeeDashboardWidget($widget): void
    {
        $widget = $this->createElement($widget);
        self::assertFalse($widget->isValid());
    }

    /**
     * Assert text by label in dashboard widget config data.
     *
     * Example: Then I should see "Bar Widget" dashboard widget config data:
     *               | Owners | All owners |
     *          Then I should see "BarWidget" dashboard widget config data:
     *               | Date range | <Date: 1900-01-01> | <Date: today> |
     *
     * @Then /^(?:|I )should see "(?P<widgetName>(?:[^"]|\\")*)" dashboard widget config data:$/
     */
    public function iShouldSeeWidgetConfigData(string $widgetName, TableNode $table): void
    {
        $widget = $this->createElement($widgetName);

        foreach ($table->getRows() as $row) {
            $value = Form::normalizeValue($row[1]);

            $expectedData = $row[0] . ': ' . $value;
            $dateRangeEndValue = $row[2] ?? null;
            if ($value && $dateRangeEndValue) {
                $expectedData .= ' - ' . Form::normalizeValue($dateRangeEndValue);
            }

            self::assertStringContainsString(
                $expectedData,
                $widget->getText(),
                sprintf('Widget config data "%s" not found in "%s" dashboard widget.', $expectedData, $widgetName)
            );
        }
    }

    /**
     * Assert text by label is not present in dashboard widget config data.
     *
     * Example: Then I should not see "Bar Widget" dashboard widget config data:
     *               | Owners | All owners |
     *          Then I should not see "BarWidget" dashboard widget config data:
     *               | Date range | <Date: 1900-01-01> | <Date: today> |
     *
     * @Then /^(?:|I )should not see "(?P<widgetName>(?:[^"]|\\")*)" dashboard widget config data:$/
     */
    public function iShouldNotSeeWidgetConfigData(string $widgetName, TableNode $table): void
    {
        $widget = $this->createElement($widgetName);

        foreach ($table->getRows() as $row) {
            $value = Form::normalizeValue($row[1]);

            $expectedData = $row[0] . ': ' . $value;
            $dateRangeEndValue = $row[2] ?? null;
            if ($value && $dateRangeEndValue) {
                $expectedData .= ' - ' . Form::normalizeValue($dateRangeEndValue);
            }

            self::assertStringNotContainsString(
                $expectedData,
                $widget->getText(),
                sprintf('Widget config data "%s" found in "%s" dashboard widget.', $expectedData, $widgetName)
            );
        }
    }

    /**
     * Example: Given I click "Configure" in "Leads List" widget
     *
     * @Given /^(?:|I )click "(?P<needle>[\w\s]+)" in "(?P<widgetName>[\w\s]+)" widget$/
     */
    public function iClickLinkInDashboardWidget($needle, $widgetName): void
    {
        $widget = $this->createElement($widgetName);
        self::assertTrue($widget->isValid());
        $widget->clickLink($needle);
    }
}
