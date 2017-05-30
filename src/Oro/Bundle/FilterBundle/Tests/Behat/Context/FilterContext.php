<?php

namespace Oro\Bundle\FilterBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
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
            list($filter, $column, $condition) = array_pad($row, 3, null);
            $this->addFilter($filter, $column, $condition);
        }
    }

    /**
     * @param string      $filter
     * @param string      $column
     * @param string|null $condition
     */
    private function addFilter($filter, $column, $condition)
    {
        $this->dragFilterToConditionBuilder($filter);
        $this->chooseFilterColumn($column);
        if ($condition) {
            $this->setFilterCondition($condition);
        }
    }

    /**
     * Method implements drag'n'drop specific filter type to configuration zone
     *
     * @param $filter
     */
    private function dragFilterToConditionBuilder($filter)
    {
        $filterElement = $this->getPage()
            ->find(
                'xpath',
                "//li[contains(., '{$filter}')]"
            );
        $dropZone = $this->createElement('Filters condition builder');
        $filterElement->dragTo($dropZone);
    }

    /**
     * @param string $column
     */
    private function chooseFilterColumn($column)
    {
        $lastConditionItem = $this->createElement('Last condition item');
        $lastConditionItem->click();
        $this->getPage()
            ->find(
                'xpath',
                "//div[@id='select2-drop']/div/input"
            )
            ->setValue($column);
        $this->waitForAjax();
        $this->getPage()
            ->find(
                'xpath',
                "//div[@id='select2-drop']//div[contains(., '{$column}')]"
            )
            ->click();
    }

    /**
     * @param string $condition
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function setFilterCondition($condition)
    {
        $this->getPage()
            ->find('xpath', "(//a[contains(@class, 'dropdown-toggle')] | //div/div/button)[last()]")
            ->click();
        $this->getPage()
            ->find('xpath', "(//span[contains(., '{$condition}')] | //li/a[contains(., '{$condition}')])[last()]")
            ->click();
    }
}
