<?php

namespace Oro\Bundle\CronBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Jobs
 * @package Oro\Bundle\CronBundle\Tests\Selenium\Pages
 * @method Jobs openJobs(string $bundlePath)
 * @method Job open(array $filter)
 * {@inheritdoc}
 */
class Jobs extends AbstractPageFilteredGrid
{
    const URL = 'cron/job';

    public function entityView()
    {
        return new Job($this->test);
    }

    public function entityNew()
    {
        return new Job($this->test);
    }

    /**
     * Method start Daemon if it is not running
     * @return $this
     */
    public function runDaemon()
    {
        $daemonState = "//p[contains(., 'Daemon status:')][contains(., 'Not running')]";
        if ($this->isElementPresent($daemonState)) {
            $this->test->url('/cron/job/run-daemon');
            $timeOut = 0;
            while ($this->isElementPresent($daemonState)) {
                if ($timeOut>MAX_EXECUTION_TIME) {
                    break;
                }
                sleep(5);
                $this->refresh();
                $timeOut = $timeOut+300;
            }
        }
        $this->assertElementPresent(
            "//p[contains(., 'Daemon status:')][contains(., 'Running')]",
            "Daemon not started as expected"
        );

        return $this;
    }
}
