<?php

namespace Oro\Bundle\ReportBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class FilterConditions
 *
 * @package Oro\Bundle\ReportBundle\Tests\Selenium\Pages
 */
abstract class FilterConditions extends AbstractPageEntity
{
    /**
     * Method implements report and segment filter functionality
     * @param $filter
     * @param $column
     * @param array $value Can be 1 element ot two elements Data start and Data end
     * @return $this
     */
    public function addFilterCondition($filter, $column, $value)
    {
        $this->addFilter($filter);

        $this->test->byXPath("(//div[@class='condition-item'])[1]//a[contains(.,'Choose a field')]")->click();
        $this->waitForAjax();
        $this->test->byXPath("//div[@id='select2-drop']/div/input")->value($column);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$column}')]",
            "Condition autocomplete doesn't return search value"
        );
        $this->test->byXPath("//div[@id='select2-drop']//div[contains(., '{$column}')]")->click();

        switch ($filter) {
            case 'Activity':
            case 'Data audit':
                $this->test->byXPath(
                    "//div[@class='filter-start-date']//input[@placeholder='Choose a date']"
                )->value($value['Start']);
                $this->test->byXPath(
                    "//div[@class='filter-end-date']//input[@placeholder='Choose a date']"
                )->value($value['End']);
                break;
            case 'Field condition':
                $this->test->byXPath(
                    "(//div[@class='condition-item'])[1]//input[@name='value']"
                )->value($value);
                break;
        }

        return $this;
    }

    /**
     * Method implements drag'n'drop specific filter type to configuration zone
     * @param $filterType
     * @return $this
     */
    protected function addFilter($filterType)
    {
        $filter = 'Filter not found';
        switch ($filterType) {
            case 'Data audit':
                $filter = 'condition-data-audit';
                break;
            case 'Field condition':
                $filter = 'condition-item';
                break;
            case 'Activity':
                $filter = 'condition-activity';
                break;
        }
        $element = $this->test->byXPath("//li[@data-criteria='{$filter}']");
        $element1 = $this->test->byXPath(
            "//div[@class='condition-builder left-panel-container']//ul[@class='conditions-group ui-sortable']"
        );
        $this->test->moveto($element);
        $this->test->buttondown();
        $this->test->moveto($element1);
        $this->test->buttonup();

        return $this;
    }

    public function removeFilterCondition()
    {
        $this->test->byXPath("//div[@class='condition-item']/preceding-sibling::a[@class='close']")->click();
        $this->waitForAjax();
        
        return $this;
    }
}
