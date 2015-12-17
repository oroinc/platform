<?php

namespace Oro\Bundle\CronBundle\Helper;

class CronHelper
{
    /**
     * @param $definition
     * @return \Cron\CronExpression
     */
    public function createCron($definition)
    {
        $cron = \Cron\CronExpression::factory($definition);

        return $cron;
    }
}
