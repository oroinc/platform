<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CronDefinitionsLoadCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testShouldLoadCommandDefinitionFromApplication()
    {
        /** @var EntityRepository $scheduleRepository */
        $scheduleRepository = self::getContainer()->get('doctrine')->getRepository(Schedule::class);
        $scheduleRepository->createQueryBuilder('d')->delete()->getQuery()->execute();

        // guard
        $schedules = $scheduleRepository->findAll();
        self::assertCount(0, $schedules);

        $result = self::runCommand('oro:cron:definitions:load');

        self::assertStringContainsString('Removing all previously loaded commands...', $result);
        self::assertStringContainsString('Processing command "oro:cron:test:usual": setting up schedule.', $result);
        self::assertStringContainsString('Processing command "oro:cron:test:lazy": setting up schedule.', $result);
        self::assertStringContainsString(
            'Processing command "oro:cron:test:no_schedule_definition":'
            . ' Skipping, the command does not implement CronCommandScheduleDefinitionInterface.',
            $result
        );

        $schedules = $scheduleRepository->findAll();
        self::assertGreaterThan(0, count($schedules));
    }
}
