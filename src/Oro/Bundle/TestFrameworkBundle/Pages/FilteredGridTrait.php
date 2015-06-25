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
            . "/a[contains(.,'{$filterName}')]"
        )->click();

        $criteria = $this->test->byXPath(
            "{$this->filtersPath}//div[contains(@class, 'filter-box')]//div[contains(@class, 'filter-item')]"
            . "[a[contains(.,'{$filterName}')]]/div[contains(@class, 'filter-criteria')]"
        );
        $input = $criteria->element($this->test->using('xpath')->value("div/div/input[@name='value']"));

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
}
