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
        $this->getScheduleRepository()->createQueryBuilder('d')->delete()->getQuery()->execute();

        $schedules = $this->getScheduleRepository()->findAll();

        //guard
        $this->assertCount(0, $schedules);

        $result = $this->runCommand('oro:cron:definitions:load');

        static::assertStringContainsString('Removing all previously loaded commands...', $result);
        static::assertStringContainsString('Processing command ', $result);
        static::assertStringContainsString(' setting up schedule..', $result);

        $schedules = $this->getScheduleRepository()->findAll();

        $this->assertGreaterThan(0, count($schedules));
    }

    public function testShouldNotLoadCommandDefinitionFromApplicationIfNotImplement()
    {
        $this->markTestIncomplete('Requires a proper test stub as it now excludes itself from the list');
        $result = $this->runCommand('oro:cron:definitions:load');

        static::assertStringContainsString(
            'Processing command "oro:cron:definitions:load": '.
            'Skipping, the command does not implement CronCommandInterface',
            $result
        );
    }

    /**
     * @return EntityRepository
     */
    private function getScheduleRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(Schedule::class);
    }
}
