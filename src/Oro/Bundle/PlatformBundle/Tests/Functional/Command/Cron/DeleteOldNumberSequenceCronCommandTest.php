<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Command\Cron;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteOldNumberSequenceTopic;
use Oro\Bundle\PlatformBundle\Command\Cron\DeleteOldNumberSequenceCronCommand;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Tests\Functional\DataFixtures\LoadNumberSequences;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

/**
 * @dbIsolationPerTest
 */
class DeleteOldNumberSequenceCronCommandTest extends WebTestCase
{
    use CommandTestingTrait;
    use MessageQueueExtension;

    private const array EXPECTED_SEQUENCE_TYPES = [
        ['sequenceType' => 'invoice', 'discriminatorType' => 'regular'],
        ['sequenceType' => 'order', 'discriminatorType' => 'regular'],
    ];
    private const int EXPECTED_SEQUENCE_COUNT = 3;

    private Connection $connection;
    private ObjectRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadNumberSequences::class]);
        $this->connection = self::getContainer()->get('doctrine')->getConnection();
        $this->repository = self::getContainer()->get('doctrine')->getRepository(NumberSequence::class);

        self::assertCount(
            self::EXPECTED_SEQUENCE_COUNT,
            $this->repository->findUniqueSequenceTypes(),
            'Expected sequence types not loaded correctly.'
        );
    }

    public function testExecute(): void
    {
        $commandTester = $this->doExecuteCommand(DeleteOldNumberSequenceCronCommand::getDefaultName());

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains(
            $commandTester,
            sprintf('Deletion has been queued for %d sequence types.', self::EXPECTED_SEQUENCE_COUNT)
        );

        $messages = self::getSentMessagesByTopic(DeleteOldNumberSequenceTopic::getName());

        self::assertCount(
            self::EXPECTED_SEQUENCE_COUNT,
            $messages,
            'Expected messages for all sequence type combinations.'
        );

        foreach (self::EXPECTED_SEQUENCE_TYPES as $expected) {
            self::assertContainsEquals(
                $expected,
                $messages,
                sprintf(
                    'Message for sequenceType "%s" and discriminatorType "%s" not found.',
                    $expected['sequenceType'],
                    $expected['discriminatorType']
                )
            );
        }
    }

    public function testExecuteWhenNothingToSchedule(): void
    {
        $this->connection->executeQuery('DELETE FROM oro_number_sequence');

        self::assertCount(
            0,
            $this->repository->findUniqueSequenceTypes(),
            'Expected no sequences after deletion.'
        );

        $commandTester = $this->doExecuteCommand(DeleteOldNumberSequenceCronCommand::getDefaultName());
        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Deletion has been queued for 0 sequence types.');

        $messages = self::getSentMessagesByTopic(DeleteOldNumberSequenceTopic::getName());
        self::assertEmpty($messages, 'Expected no messages when no sequences exist.');
    }

    public function testIsActive(): void
    {
        $command = self::getContainer()->get('oro_platform.command.delete_old_number_sequence');
        self::assertTrue($command->isActive(), 'Command should be active when sequences exist.');

        $this->connection->executeQuery('DELETE FROM oro_number_sequence');
        self::assertFalse($command->isActive(), 'Command should not be active when no sequences exist.');
    }
}
