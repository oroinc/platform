<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Helper;

use Cron\CronExpression;
use Oro\Bundle\CronBundle\Helper\CronHelper;

class CronHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var CronHelper */
    protected $cronHelper;

    /**
     * {@inheritdoc}
     */
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
                'expectedCronExpression' => CronExpression::factory('* * * * *')
            ],
            'yearly' => [
                'definition' => '@yearly',
                'expectedCronExpression' => CronExpression::factory('0 0 1 1 *')
            ],
            'annually' => [
                'definition' => '@annually',
                'expectedCronExpression' => CronExpression::factory('0 0 1 1 *')
            ],
            'monthly' => [
                'definition' => '@monthly',
                'expectedCronExpression' => CronExpression::factory('0 0 1 * *')
            ],
            'weekly' => [
                'definition' => '@weekly',
                'expectedCronExpression' => CronExpression::factory('0 0 * * 0')
            ],
            'daily' => [
                'definition' => '@daily',
                'expectedCronExpression' => CronExpression::factory('0 0 * * *')
            ],
            'hourly' => [
                'definition' => '@hourly',
                'expectedCronExpression' => CronExpression::factory('0 * * * *')
            ],
            'prevent modulo by zero' => [
                'definition' =>  '*/0 * * * *',
                'expectedCronExpression' => CronExpression::factory('* * * * *')
            ],
        ];
    }
}
