<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Tools\CronHelper;

/**
 * The decorator for CronHelper that allows to substitute CRON expression to be created in functional tests.
 */
class CronHelperStub extends CronHelper
{
    private CronHelper $cronHelper;
    private ?CronExpression $cron = null;

    public function __construct(CronHelper $cronHelper)
    {
        $this->cronHelper = $cronHelper;
    }

    public function setCron(?CronExpression $cron): void
    {
        $this->cron = $cron;
    }

    public function createCron(string $definition): CronExpression
    {
        return $this->cron ?? $this->cronHelper->createCron($definition);
    }
}
