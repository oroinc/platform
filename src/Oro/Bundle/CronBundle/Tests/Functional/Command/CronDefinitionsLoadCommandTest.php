<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CronDefinitionsLoadCommandTest extends WebTestCase
{
    protected function setUp()
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

        $this->assertContains('Removing all previously loaded commands...', $result);
        $this->assertContains('Processing command ', $result);
        $this->assertContains(' setting up schedule..', $result);

        $schedules = $this->getScheduleRepository()->findAll();

        $this->assertGreaterThan(0, count($schedules));
    }

    public function testShouldNotLoadCommandDefinitionFromApplicationIfNotImplement()
    {
        $result = $this->runCommand('oro:cron:definitions:load');

        $this->assertContains(
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
