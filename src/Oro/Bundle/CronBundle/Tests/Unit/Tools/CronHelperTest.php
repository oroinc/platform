<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Tools;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Tools\CronHelper;
use PHPUnit\Framework\TestCase;

class CronHelperTest extends TestCase
{
    private CronHelper $cronHelper;

    #[\Override]
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
