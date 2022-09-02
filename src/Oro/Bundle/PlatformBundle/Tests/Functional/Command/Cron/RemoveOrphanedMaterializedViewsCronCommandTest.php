<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Command\Cron;

use Oro\Bundle\PlatformBundle\Command\Cron\RemoveOrphanedMaterializedViewsCronCommand;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView as MaterializedViewEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class RemoveOrphanedMaterializedViewsCronCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testExecute(): void
    {
        $this->loadFixtures(['@OroPlatformBundle/Tests/Functional/DataFixtures/orphaned_materialized_views.yml']);

        $repository = self::getContainer()->get('doctrine')->getRepository(MaterializedViewEntity::class);
        self::assertCount(1, $repository->findOlderThan(new \DateTime('today -7 days', new \DateTimeZone('UTC'))));

        $commandTester = $this->doExecuteCommand(RemoveOrphanedMaterializedViewsCronCommand::getDefaultName());

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains(
            $commandTester,
            sprintf(
                '1 orphaned materialized views older than 7 days have been successfully removed: %s',
                $this->getReference('materialized_view_7_days_old')->getName()
            )
        );

        self::assertCount(0, $repository->findOlderThan(new \DateTime('today -7 days', new \DateTimeZone('UTC'))));
    }

    public function testExecuteWhenNothingToRemove(): void
    {
        $this->loadFixtures(['@OroPlatformBundle/Tests/Functional/DataFixtures/orphaned_materialized_views.yml']);

        $repository = self::getContainer()->get('doctrine')->getRepository(MaterializedViewEntity::class);
        self::assertCount(2, $repository->findAll());

        $commandTester = $this->doExecuteCommand(
            RemoveOrphanedMaterializedViewsCronCommand::getDefaultName(),
            ['--days-old' => 10]
        );

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'There are no orphaned materialized views older than 10 days');

        self::assertCount(2, $repository->findAll());
    }

    /**
     * @dataProvider invalidDaysOldOptionDataProvider
     */
    public function testExecuteWhenInvalidDaysOldOption(int|string $daysOld): void
    {
        $commandTester = $this->doExecuteCommand(
            RemoveOrphanedMaterializedViewsCronCommand::getDefaultName(),
            ['--days-old' => $daysOld]
        );

        $this->assertProducedError(
            $commandTester,
            'Option "days-old" must be a positive number, got "' . $daysOld . '"'
        );
    }

    public function invalidDaysOldOptionDataProvider(): array
    {
        return [[0], [-1], ['invalid']];
    }
}
