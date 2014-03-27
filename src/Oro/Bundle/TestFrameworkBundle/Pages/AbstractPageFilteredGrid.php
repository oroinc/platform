<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages;

/**
 * Class AbstractPageFilteredGrid
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages
 * {@inheritdoc}
 */
abstract class AbstractPageFilteredGrid extends AbstractPageGrid
{
    /**
     * Remove filter
     *
     * @param string $filterName
     * @return $this
     */
    public function removeFilter($filterName)
    {
        $this->test->byXPath(
            "{$this->filtersPath}//div[contains(@class, 'filter-box')]//div[contains(@class, 'filter-item')]"
            . "[a[contains(.,'{$filterName}')]]/a[contains(., 'Close')]"
        )->click();
        $this->waitForAjax();
        return $this;
    }

    /**
     * Add filter
     *
     * @param string $filterName
     * @return $this
     */
    public function addFilter($filterName)
    {
        $addFilter = $this->test->byXPath(
            "{$this->filtersPath}//div[contains(@class, 'filter-box')]//button[contains(.,'Manage filters')]"
        );
        //expand filter list
        $addFilter->click();
        $filter = $this->test->byXPath(
            "{$this->filtersPath}//input[@title[normalize-space(.)='{$filterName}']]" .
            "[@name='multiselect_add-filter-select']"
        );
        if (!$filter->selected()) {
            $filter->click();
        }
        $this->waitForAjax();
        //hide filter list
        $addFilter->click();
        $this->waitForAjax();
        return $this;
    }

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
            . "/a[contains(.,'{$filterName}')]"
        )->click();

        $criteria = $this->test->byXPath(
            "{$this->filtersPath}//div[contains(@class, 'filter-box')]//div[contains(@class, 'filter-item')]"
            . "[a[contains(.,'{$filterName}')]]/div[contains(@class, 'filter-criteria')]"
        );
        $input = $criteria->element($this->test->using('xpath')->value("div/div/div/input[@name='value']"));

        $input->clear();
        $input->value($value);

        //select criteria
        if ($condition != '') {
            //expand condition list
            $criteria->element($this->test->using('xpath')->value("div/div/div/button[@class ='btn dropdown-toggle']"))
                ->click();

            $criteria->element($this->test->using('xpath')->value("div/div/div/ul/li/a[text()='{$condition}']"))
                ->click();
        }
        $criteria->element($this->test->using('xpath')->value("div/button[contains(@class, 'filter-update')]"))
            ->click();
        $this->waitForAjax();
        return $this;
    }

    /**
     * Clear filter value and apply
     *
     * @param string $filterName
     * @return $this
     */
    public function clearFilter($filterName)
    {
        $this->filterBy($filterName);
        return $this;
    }
}
