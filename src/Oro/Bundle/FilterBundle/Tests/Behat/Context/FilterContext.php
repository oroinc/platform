<?php

namespace Oro\Bundle\FilterBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
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
            list($filter, $column, $type) = $row;
            $this->addFilter($filter, $column, $type);
        }
    }

    /**
     * @param string $value
     */
    private function selectValue($value)
    {
        $this->getPage()
            ->find(
                'xpath',
                "//div[@id='select2-drop']/div/input"
            )
            ->setValue($value);
        $this->getPage()
            ->find(
                'xpath',
                "//div[@id='select2-drop']//div[contains(., '{$value}')]"
            )
            ->click();
    }

    /**
     * @param string $filter
     * @param string $column
     * @param string $condition
     */
    private function addFilter($filter, $column, $condition)
    {
        $this->dragFilterToConditionBuilder($filter);
        $this->chooseFilterColumn($column);
        $this->setFilterCondition($condition);
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
     * @param $column
     */
    private function chooseFilterColumn($column)
    {
        $this->clickLinkInFiltersZone('Choose a field');
        $this->selectValue($column);
    }

    /**
     * @param string $link
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function clickLinkInFiltersZone($link)
    {
        $filtersZone = $this->createElement('Filters condition builder');
        $filtersZone->clickLink($link);
    }

    /**
     * @param string $condition
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function setFilterCondition($condition)
    {
        $filterCondition = $this->getPage()->find('xpath', '(//span[contains(@class, "active-filter")])[last()]');
        $filterCondition->find('css', 'a.dropdown-toggle')->click();
        $filterCondition->clickLink($condition);
    }
}
