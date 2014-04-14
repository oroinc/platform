<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages;

/**
 * Class Task
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages
 */
class Workflow
{
    /**
     * @param AbstractPage $page
     * @param array $steps
     *
     * @return mixed
     */
    public function process($page, array $steps)
    {
        foreach ($steps as $step => $value) {
            $page->getTest()->byXPath("//a[@title = '{$step}']")->click();
            $page->waitPageToLoad();
            $page->waitForAjax();
            //verify step widget
            if ($value !== '' && !is_null($value)) {
                $page->assertElementPresent(
                    "//ul[contains(@class, 'workflow-step-list')]" .
                    "/li[@class = 'current' and normalize-space(.)='{$value}']"
                );
            }
        }
        return $page;
    }
}
