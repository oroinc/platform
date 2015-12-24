<?php

namespace Oro\Bundle\CronBundle\Helper;

use Cron\CronExpression;

class CronHelper
{
    /**
     * Create a new CronExpression.
     *
     * @param string $definition The CRON expression to create.  There are
     *                           several special predefined values which can be used to substitute the
     *                           CRON expression:
     *
     *      `@yearly`, `@annually` - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
     *      `@monthly` - Run once a month, midnight, first of month - 0 0 1 * *
     *      `@weekly` - Run once a week, midnight on Sun - 0 0 * * 0
     *      `@daily` - Run once a day, midnight - 0 0 * * *
     *      `@hourly` - Run once an hour, first minute - 0 * * * *
     *
     * @return CronExpression
     */
    public function createCron($definition)
    {
        return CronExpression::factory($definition);
    }
}
