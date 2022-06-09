<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Tools;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Tools\CronHelper;

class CronHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var CronHelper */
    private $cronHelper;

    protected function setUp(): void
    {
        $this->cronHelper = new CronHelper();
    }

    /**
     * @dataProvider cronDataProvider
     */
    public function testCreateCron(string $definition, CronExpression $expectedCronExpression): void
    {
        $cronExpression = $this->cronHelper->createCron($definition);
        $cronExpression->isDue();
        $this->assertEquals($expectedCronExpression, $cronExpression);
    }

    public function cronDataProvider(): array
    {
        return [
            'simple definition' => [
                'definition' => '* * * * *',
                'expectedCronExpression' => new CronExpression('* * * * *')
            ],
            'yearly' => [
                'definition' => '@yearly',
                'expectedCronExpression' => new CronExpression('0 0 1 1 *')
            ],
            'annually' => [
                'definition' => '@annually',
                'expectedCronExpression' => new CronExpression('0 0 1 1 *')
            ],
            'monthly' => [
                'definition' => '@monthly',
                'expectedCronExpression' => new CronExpression('0 0 1 * *')
            ],
            'weekly' => [
                'definition' => '@weekly',
                'expectedCronExpression' => new CronExpression('0 0 * * 0')
            ],
            'daily' => [
                'definition' => '@daily',
                'expectedCronExpression' => new CronExpression('0 0 * * *')
            ],
            'hourly' => [
                'definition' => '@hourly',
                'expectedCronExpression' => new CronExpression('0 * * * *')
            ],
            'prevent modulo by zero' => [
                'definition' =>  '*/0 * * * *',
                'expectedCronExpression' => new CronExpression('* * * * *')
            ],
        ];
    }
}
