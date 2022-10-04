<?php

namespace Oro\Bundle\FilterBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entities;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\SelectorManipulator;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use WebDriver\Exception\NoSuchElement;
use WebDriver\Key;

class FilterContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given /^(?:|I )add the following filters:$/
     */
    public function iAddTheFollowingFilters(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            [$filter, $column, $type, $value] = array_pad($row, 4, null);
            $this->addFilter($filter, $column, $type, $value);
            $this->waitForAjax();
        }
    }

    /**
     * Method implements drag'n'drop specific filter type to configuration zone
     *
     * @Given /^(?:|I )add "(?P<filter>(?:[^"]|\\")*)" filter$/
     *
     * @param string $filter
     */
    public function dragFilterToConditionBuilder($filter)
    {
        $filterElement = $this->getPage()
            ->find('xpath', "//li[contains(., '{$filter}')]");
        $dropZone = $this->createElement('FiltersConditionBuilder');
        $filterElement->dragTo($dropZone);
    }

    /**
     * @Given /^(?:|I )choose "(?P<column>(?:[^"]|\\")*)" filter column/
     *
     * @param string $column
     */
    public function chooseFilterColumn($column)
    {
        $selectorManipulator = new SelectorManipulator();
        $lastConditionItem = $this->createElement('Last condition item');
        $lastConditionItem->click();
        $this->getPage()
            ->find('xpath', "//div[@id='select2-drop']/div/input")
            ->setValue($column);
        $this->waitForAjax();

        $columnParts = array_map('trim', explode('>', $column));

        foreach ($columnParts as $column) {
            $searchResult = $this->spin(function (FilterContext $context) use ($column, $selectorManipulator) {
                $selector = $selectorManipulator->getContainsXPathSelector("//div[@id='select2-drop']//div", $column);
                $searchResult = $this->getPage()->find($selector['type'], $selector['locator']);
                if ($searchResult && $searchResult->isVisible()) {
                    return $searchResult;
                }

                return null;
            }, 5);

            self::assertNotNull($searchResult, sprintf('No search results for "%s"', $column));
            $searchResult->click();
        }
    }

    /**
     * @param string $filter
     * @param string $column
     * @param string|null $condition
     * @param string|null $value
     */
    private function addFilter($filter, $column, $condition, $value)
    {
        $this->dragFilterToConditionBuilder($filter);
        $this->chooseFilterColumn($column);
        if ($condition) {
            $this->setFilterCondition($condition);
        }
        if ($value) {
            $this->setFilterValue($value, $condition);
        }
    }

    /**
     * @param string $condition
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function setFilterCondition($condition)
    {
        $dropdown = $this->createElement('FilterConditionDropdown');
        if ($dropdown->isValid() && $dropdown->isVisible()) {
            $dropdown->click();
        } else {
            $button = $this->createElement('FilterConditionDropdownButton');
            if ($button->isVisible()) {
                $button->click();
            }
        }
        $this->getPage()
             ->find('xpath', "(//span[contains(., '{$condition}')] | //li/a[contains(., '{$condition}')])[last()]")
             ->click();
    }

    /**
     * @param string $value
     * @param $condition
     */
    private function setFilterValue($value, $condition)
    {
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        try {
            $inputXpath = "//span[contains(@class, 'active-filter')]"
                . "//a[contains(@class, 'dropdown-toggle') and contains(., '{$condition}')]"
                . "/following-sibling::input[contains(@name, 'value')]";

            $driver->typeIntoInput($inputXpath, $value);
        } catch (NoSuchElement $e) {
            $inputXpath = "//span[contains(@class, 'active-filter')]"
                . "//a[contains(@class, 'dropdown-toggle') and contains(., '{$condition}')]"
                . "/../following-sibling::div[contains(@class, 'select2-container')]"
                . "//input[contains(@class, 'select2-input')]";

            $select2Element = $this->getPage()->find('xpath', $inputXpath);

            /** @var Select2Entities $select2Entities */
            $select2Entities = $this->elementFactory->wrapElement('Select2Entities', $select2Element);
            if ($select2Element->isVisible()) {
                $select2Entities->setValue($value);
            }
        }
    }

    /**
     * @Given /^(?:|I )should see "(?P<column>(?:[^"]|\\")*)" in the field condition filter select/
     */
    public function shouldSeeInTheFieldConditionSelect(string $column)
    {
        $this->checkInTheFieldConditionSelect($column, true);
    }

    /**
     * @Given /^(?:|I )should not see "(?P<column>(?:[^"]|\\")*)" in the field condition filter select/
     */
    public function shouldNotSeeInTheFieldConditionSelect(string $column)
    {
        $this->checkInTheFieldConditionSelect($column, false);
    }

    private function checkInTheFieldConditionSelect(string $column, bool $isShouldSee): void
    {
        $lastConditionItem = $this->createElement('Last condition item');
        $lastConditionItem->click();

        $searchResult = $this->spin(function (FilterContext $context) use ($column) {
            $searchResult = $this->getPage()
                ->find(
                    'xpath',
                    "//div[@id='select2-drop']//div[contains(., '{$column}')]"
                );
            if ($searchResult && $searchResult->isVisible()) {
                return $searchResult;
            }

            return null;
        }, 5);

        if ($isShouldSee === true) {
            self::assertNotNull(
                $searchResult,
                sprintf('The field "%s" was not found in the filter columns.', $column)
            );
        } else {
            self::assertNull(
                $searchResult,
                sprintf('The field "%s" appears in the filter columns, but it should not.', $column)
            );
        }

        $this->getDriver()->typeIntoInput("//div[@id='select2-drop']/div/input", Key::ESCAPE);
    }
}
