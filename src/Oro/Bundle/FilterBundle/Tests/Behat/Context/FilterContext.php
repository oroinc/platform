<?php

namespace Oro\Bundle\FilterBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FilterContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given I add the following filters:
     *
     * @param TableNode $table
     */
    public function iAddTheFollowingFilters(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            list($filter, $column, $type, $value) = array_pad($row, 4, null);
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
        $lastConditionItem = $this->createElement('Last condition item');
        $lastConditionItem->click();
        $this->getPage()
            ->find('xpath', "//div[@id='select2-drop']/div/input")
            ->setValue($column);
        $this->waitForAjax();

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

        self::assertNotNull($searchResult, sprintf('No search results for "%s"', $column));
        $searchResult->click();
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
        $this->createElement('FilterConditionDropdown')->click();
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
        $inputXpath = "//span[contains(@class, 'active-filter')]"
                      . "//a[contains(@class, 'dropdown-toggle') and contains(., '{$condition}')]"
                      . "/following-sibling::input[contains(@name, 'value')]";
        $driver->typeIntoInput($inputXpath, $value);
    }
}
