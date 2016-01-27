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
    use FilteredGridTrait;
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
            . "[*[contains(@class,'filter-criteria-selector')][contains(.,'{$filterName}')]]/a[contains(., 'Close')]"
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
            "{$this->filtersPath}//div[contains(@class, 'filter-box')]//button//a[@class='add-filter-button']"
        );
        //expand filter list
        $addFilter->click();
        $filter = $this->test->byXPath(
            "{$this->filtersPath}//input[@title[normalize-space(.)='{$filterName}']][@type='checkbox']"
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
