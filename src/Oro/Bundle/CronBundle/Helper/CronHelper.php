<?php

namespace Oro\Bundle\CronBundle\Helper;

use Cron\CronExpression;

class CronHelper
{
    /**
     * @param $definition
     *
     * @return \Cron\CronExpression
     */
    public function createCron($definition)
    {
        $cron = CronExpression::factory($definition);

        return $cron;
    }
}
