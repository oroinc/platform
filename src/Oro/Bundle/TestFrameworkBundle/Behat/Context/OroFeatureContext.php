<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;

class OroFeatureContext extends RawMinkContext
{
    use AssertTrait;

    /**
     * @param \Closure $lambda
     * @return false|mixed Return false if closure throw error or return not true value.
     *                     Return value that return closure
     */
    public function spin(\Closure $lambda)
    {
        $time = 60;

        while ($time > 0) {
            try {
                if ($result = $lambda($this)) {
                    return $result;
                }
            } catch (\Exception $e) {
                // do nothing
            }
            usleep(250000);
            $time -= 0.25;
        }
        return false;
    }

    /**
     * @param int $time
     */
    public function waitForAjax($time = 60000)
    {
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $driver->waitForAjax($time);
    }

    /**
     * @param int|string $count
     * @return int
     */
    protected function getCount($count)
    {
        switch (trim($count)) {
            case '':
                return 1;
            case 'one':
                return 1;
            case 'two':
                return 2;
            default:
                return (int) $count;
        }
    }
}
