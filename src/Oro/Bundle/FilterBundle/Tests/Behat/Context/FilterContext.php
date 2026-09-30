<?php

namespace Oro\Bundle\FilterBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entities;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\SelectorManipulator;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\VariableStorage;
use WebDriver\Key;

class FilterContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given /^(?:|I )add the following filters:$/
     */
    public function iAddTheFollowingFilters(TableNode $table)
    {
        $this->waitForAjax();
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
        $option = $this->spin(function (FilterContext $context) use ($condition) {
            $option = $context->getPage()
                ->find('xpath', "(//span[contains(., '{$condition}')] | //li/a[contains(., '{$condition}')])[last()]");

            return $option && $option->isVisible() ? $option : null;
        }, 5);

        self::assertNotNull($option, sprintf('Filter condition "%s" did not appear in the dropdown.', $condition));

        $option->click();
    }

    /**
     * @param string $value
     * @param $condition
     */
    private function setFilterValue($value, $condition)
    {
        $value = VariableStorage::normalizeValue($value);
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();

        $condTail = "//a[contains(@class, 'dropdown-toggle') and contains(., '{$condition}')]";
        $plainXpath = "//span[contains(@class, 'active-filter')]" . $condTail
            . "/following-sibling::input[contains(@name, 'value')]";
        $select2Xpath = "//span[contains(@class, 'active-filter')]" . $condTail
            . "/../following-sibling::div[contains(@class, 'select2-container')]"
            . "//input[contains(@class, 'select2-input')]";

        // The condition choice renders the card again: a plain input for a scalar field, a select2 for a multienum.
        // A read right after the click finds the card in the middle of the render, so wait for the widget.
        $field = $this->spin(function (FilterContext $context) use ($plainXpath, $select2Xpath) {
            foreach (['plain' => $plainXpath, 'select2' => $select2Xpath] as $kind => $xpath) {
                $element = $context->getPage()->find('xpath', $xpath);
                if ($element && $element->isVisible()) {
                    return [$kind, $element];
                }
            }

            return null;
        }, 5);

        self::assertNotNull($field, sprintf('No value input rendered for filter condition "%s".', $condition));

        [$kind, $element] = $field;

        if ('plain' === $kind) {
            $driver->typeIntoInput($element->getXpath(), $value);

            return;
        }

        /** @var Select2Entities $select2Entities */
        $select2Entities = $this->elementFactory->wrapElement('Select2Entities', $element);
        $select2Entities->setValue($value);
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
