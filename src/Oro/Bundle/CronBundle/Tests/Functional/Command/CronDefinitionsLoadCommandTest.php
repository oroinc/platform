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
        $nonCronSchedules = $this->loadNonCronSchedules();

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

        /** @var Schedule $expected */
        foreach ($nonCronSchedules as $expected) {
            $actual = $scheduleRepository->find($expected->getId());

            self::assertInstanceOf(Schedule::class, $actual);
            self::assertEquals($expected->getId(), $actual->getId());
            self::assertEquals($expected->getCommand(), $actual->getCommand());
            self::assertEquals($expected->getDefinition(), $actual->getDefinition());
            self::assertEquals($expected->getArguments(), $actual->getArguments());
        }
    }

    private function loadNonCronSchedules(): array
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Schedule::class);

        $item1 = $this->createSchedule(
            'oro:process:handle-trigger',
            '10 * * * *',
            ['--id=1', '--name=test_1']
        );
        $em->persist($item1);

        $item2 = $this->createSchedule(
            'oro:process:handle-trigger',
            '20 * * * *'
        );
        $em->persist($item2);

        $item3 = $this->createSchedule(
            'app:command',
            '30 * * * *',
            ['arg1', '--arg2', '--arg3=test']
        );
        $em->persist($item3);

        $item4 = $this->createSchedule(
            'app:command',
            '40 * * * *',
        );
        $em->persist($item4);
        $em->flush();

        return [$item1, $item2, $item3, $item4];
    }

    private function createSchedule(string $command, string $definition, array $arguments = []): Schedule
    {
        $entity = new Schedule();
        $entity->setCommand($command);
        $entity->setDefinition($definition);
        $entity->setArguments($arguments);

        return $entity;
    }
}
