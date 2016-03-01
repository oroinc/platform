<?php

namespace Oro\Bundle\CronBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Job
 * @package Oro\Bundle\CronBundle\Tests\Selenium\Pages
 * @method Job openJob(string $bundlePath)
 * {@inheritdoc}
 */
class Job extends AbstractPageEntity
{
    /**
     * Method reads and assert that output message contains param values
     * @param $values
     * @return $this
     */
    public function assertOutputMessages($values)
    {
        foreach ($values as $value) {
            $this->assertElementPresent(
                "//h3/following-sibling::pre[contains(., '{$value}')]",
                "Output message does not contain '{$value}'"
            );
        }

        return $this;
    }

    /**
     * Method asserts job state and if it is Pending or Running
     * will wait 5 seconds and refresh page until MAX_EXECUTION_TIME
     * @param $state
     * @return $this
     */
    public function checkJobState($state)
    {
        $timeOut = 0;
        while ($this->isElementPresent("//tr[contains(., 'State')]//span[text()='Pending']") or
            $this->isElementPresent("//tr[contains(., 'State')]//span[text()='Running']")) {
            if ($timeOut>MAX_EXECUTION_TIME) {
                break;
            }
            sleep(5);
            $this->refresh();
            $timeOut = $timeOut+300;
        }
        $stateResult = $this->test->byXPath("//tr[contains(., 'State')]//span")->text();
        $this->assertElementPresent(
            "//tr[contains(., 'State')]//span[text()='{$state}']",
            "Job not finished within {$timeOut} milliseconds and state is {$stateResult}"
        );

        return $this;
    }
}
