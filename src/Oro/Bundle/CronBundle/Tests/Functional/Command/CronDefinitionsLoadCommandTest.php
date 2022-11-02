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

        $schedules = $scheduleRepository->findAll();

        //guard
        $this->assertCount(0, $schedules);

        $result = $this->runCommand('oro:cron:definitions:load');

        self::assertStringContainsString('Removing all previously loaded commands...', $result);
        self::assertStringContainsString('Processing command ', $result);
        self::assertStringContainsString(' setting up schedule..', $result);

        $schedules = $scheduleRepository->findAll();
        $this->assertGreaterThan(0, count($schedules));
    }

    public function testShouldNotLoadCommandDefinitionFromApplicationIfNotImplement()
    {
        $this->markTestIncomplete('Requires a proper test stub as it now excludes itself from the list');
        $result = $this->runCommand('oro:cron:definitions:load');

        self::assertStringContainsString(
            'Processing command "oro:cron:definitions:load": '.
            'Skipping, the command does not implement CronCommandScheduleDefinitionInterface',
            $result
        );
    }
}
