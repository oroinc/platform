<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Command;

use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadStatusData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CleanupCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadStatusData::class]);
    }

    /**
     * @dataProvider paramProvider
     */
    public function testCommandOutput(string $expectedContent, array $params, int $rowsCount)
    {
        $result = $this->runCommand('oro:cron:integration:cleanup', $params);
        self::assertStringContainsString($expectedContent, $result);
        $totalRows = $this->getContainer()->get('doctrine')->getRepository(Status::class)->findAll();
        self::assertCount($rowsCount, $totalRows);
    }

    public function paramProvider(): array
    {
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $maxDateFromFixtures = new \DateTime('2015-02-01 00:20:00', new \DateTimeZone('UTC'));
        $interval = $maxDateFromFixtures->diff($currentDate);
        return [
            'should show help'                             => [
                '$expectedContent' => 'Usage: oro:cron:integration:cleanup [options]',
                '$params'          => ['--help'],
                '$rowsCount'       => 5
            ],
            'should show success output and records count for failed statuses' => [
                '$expectedContent' => 'Integration statuses will be deleted: 1'
                    . ' Integration statuses history cleanup completed',
                '$params'          => ['-i' => ((int)$interval->format('%a') + 3) . ' day'],
                '$rowsCount'       => 4
            ],
            'should show no records found'                 => [
                '$expectedContent' => 'There are no integration statuses eligible for clean up',
                '$params'          => ['-i' => ((int)$interval->format('%a') + 3) . ' day'],
                '$rowsCount'       => 4
            ],
            'should show success output and records count for completed statuses' => [
                '$expectedContent' => 'Integration statuses will be deleted: 2'
                    . ' Integration statuses history cleanup completed',
                '$params'          => [],
                '$rowsCount'       => 2
            ]
        ];
    }
}
