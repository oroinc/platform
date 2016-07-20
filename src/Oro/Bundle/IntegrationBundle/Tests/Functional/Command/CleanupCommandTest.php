<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CleanupCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadStatusData']);
    }

    /**
     * @dataProvider paramProvider
     *
     * @param string $expectedContent
     * @param array  $params
     */
    public function testCommandOutput($expectedContent, $params)
    {
        $result = $this->runCommand('oro:cron:integration:cleanup', $params);
        $this->assertContains($expectedContent, $result);
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        $currentDate = new \DateTime("now", new \DateTimeZone('UTC'));
        $maxDateFromFixtures = new \DateTime('2015-02-01 00:20:00', new \DateTimeZone('UTC'));
        $interval = $maxDateFromFixtures->diff($currentDate);
        return [
            'should show help'                             => [
                '$expectedContent' => "Usage:\n  oro:cron:integration:cleanup [options]",
                '$params'          => ['--help']
            ],
            'should show success output and records count for failed statuses' => [
                '$expectedContent' => "Integration statuses will be deleted: 1" . PHP_EOL
                    . "Integration statuses history cleanup completed",
                '$params'          => ['-i' => ((int)$interval->format('%a') + 3) . ' day']
            ],
            'should show no records found'                 => [
                '$expectedContent' => 'There are no integration statuses eligible for clean up',
                '$params'          => ['-i' => ((int)$interval->format('%a') + 3) . ' day']
            ],
            'should show success output and records count for completed statuses' => [
                '$expectedContent' => "Integration statuses will be deleted: 4" . PHP_EOL
                    . "Integration statuses history cleanup completed",
                '$params'          => []
            ]
        ];
    }
}
