<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages;

/**
 * @package Oro\Bundle\TestFrameworkBundle\Pages
 *
 */
trait FilteredGridTrait
{
    /**
     * Apply specific filter for current grid
     *
     * @param string $filterName
     * @param string $value
     * @param string $condition
     * @return $this
     */
    public function filterBy($filterName, $value = '', $condition = '')
    {
        $this->test->byXPath(
            "{$this->filtersPath}//div[contains(@class, 'filter-box')]//div[contains(@class, 'filter-item')]"
            . "/*[contains(@class,'filter-criteria-selector')][contains(.,'{$filterName}')]"
        )->click();

        $criteria = $this->test->byXPath(
            "{$this->filtersPath}//div[contains(@class, 'filter-box')]//div[contains(@class, 'filter-item')]"
            . "[*[contains(@class,'filter-criteria-selector')][contains(.,'{$filterName}')]]"
            . "/div[contains(@class, 'dropdown-menu')]"
        );
        $input = $criteria->element($this->test->using('xpath')->value("div/div//input[@name='value']"));

        $input->clear();
        $input->value($value);

        //select criteria
        if ($condition !== '') {
            //expand condition list
            $criteria->element($this->test->using('xpath')->value("div/div/button[@class ='btn dropdown-toggle']"))
                ->click();

            $criteria->element($this->test->using('xpath')->value("div/div/ul/li/a[text()='{$condition}']"))
                ->click();
        }
        $criteria->element($this->test->using('xpath')->value("div/div/button[contains(@class, 'filter-update')]"))
            ->click();
        $this->waitForAjax();

        return $this;
    }

    /**
     * Method to filter grid by Multiselect filter
     * @param $filterName
     * @param array $values
     * @return $this
     */
    public function filterByMultiselect($filterName, $values)
    {
        foreach ($values as $value) {
            $this->test->byXPath(
                "//div[@class='btn filter-select filter-criteria-selector filter-default-value']".
                "[contains(., '{$filterName}')]"
            )->click();
            $this->waitForAjax();
            $this->test->byXPath(
                "//div[@class='ui-corner-all ui-multiselect-header ui-helper-clearfix ui-multiselect-hasfilter']//input"
            )->value($value);
            $this->test->byXPath(
                "//ul[@class='ui-multiselect-checkboxes ui-helper-reset fixed-li']".
                "/li/label[@title='{$value}']/input"
            )->click();
            $this->waitForAjax();
        }
        $this->test->byXPath(
            "//div[@class='ui-corner-all ui-multiselect-header ui-helper-clearfix ui-multiselect-hasfilter']//input"
        )->click();
        $this->test->keys(\PHPUnit_Extensions_Selenium2TestCase_Keys::ESCAPE);

        return $this;
    }

    /**
     * Method to refresh grid
     * @return $this
     */
    public function refreshGrid()
    {
        $this->test->byXpath(
            "//div[@class='actions-panel pull-right form-horizontal']//a[contains(., 'Refresh')]"
        )->click();
        $this->waitForAjax();

        return $this;
    }
}
