<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Tools;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Tools\CronHelper;

class CronHelperTest extends \PHPUnit\Framework\TestCase
{
    private CronHelper $cronHelper;

    protected function setUp(): void
    {
        $this->cronHelper = new CronHelper();
    }

    public function testCreateCron(): void
    {
        $cronExpression = $this->cronHelper->createCron('*/0 * * * *');

        $this->assertEquals(new CronExpression('* * * * *'), $cronExpression);
    }
}
