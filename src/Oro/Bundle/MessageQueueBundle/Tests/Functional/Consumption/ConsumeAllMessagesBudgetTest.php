<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Verifies that consumeAllMessages() gives up with a diagnostic instead of looping until the test runner is killed,
 * and that purging the queue does not leave messages behind in queues the test never consumes from.
 *
 * The budget is squeezed to zero so that it is exceeded on the very first round: what is under test is the guard and
 * the diagnostics it produces, not the particular default values.
 */
class ConsumeAllMessagesBudgetTest extends WebTestCase
{
    use MessageQueueExtension;

    private static int $roundLimit = 200;

    private static int $timeLimit = 60;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        self::$roundLimit = 200;
        self::$timeLimit = 60;
    }

    protected static function getConsumeAllMessagesRoundLimit(): int
    {
        return self::$roundLimit;
    }

    protected static function getConsumeAllMessagesTimeLimit(): int
    {
        return self::$timeLimit;
    }

    public function testThatConsumingAllMessagesGivesUpWhenTheRoundBudgetIsExceeded(): void
    {
        self::$roundLimit = 0;
        self::sendMessage(PriorityLowTestTopic::getName(), ['key' => 'value']);

        $error = self::captureConsumeAllMessagesFailure();

        self::assertStringContainsString('Failed to consume all messages', $error->getMessage());
        self::assertStringContainsString('the round budget of 0', $error->getMessage());
        self::assertStringContainsString('1 message(s) still pending', $error->getMessage());
    }

    public function testThatConsumingAllMessagesGivesUpWhenTheTimeBudgetIsExceeded(): void
    {
        self::$timeLimit = 0;
        self::sendMessage(PriorityLowTestTopic::getName(), ['key' => 'value']);

        $error = self::captureConsumeAllMessagesFailure();

        self::assertStringContainsString('the time budget of 0 second(s)', $error->getMessage());
    }

    public function testThatTheFailureReportsPendingTopicsAndWhatIsLeftInTheQueue(): void
    {
        self::$roundLimit = 0;
        self::sendMessage(PriorityLowTestTopic::getName(), ['key' => 'value']);

        // Emulates a message claimed by a consumer that no longer exists: such a message stays in the table but can
        // never be received again, which is indistinguishable from an empty queue without this diagnostic.
        self::claimAllQueuedMessages('a-consumer-that-no-longer-exists');

        $error = self::captureConsumeAllMessagesFailure();

        self::assertStringContainsString(PriorityLowTestTopic::getName() . ': 1 message(s)', $error->getMessage());
        self::assertStringContainsString('oro.test.priority.low', $error->getMessage());
        self::assertStringContainsString('1 claimed by a consumer', $error->getMessage());
    }

    public function testThatPurgingTheQueueRemovesMessagesFromEveryQueue(): void
    {
        self::sendMessage(PriorityLowTestTopic::getName(), ['key' => 'value']);
        // Moves the message to a queue no consumer in this test suite is bound to: exactly the kind of message
        // the old oro.default-only purge used to leave behind.
        self::moveAllQueuedMessagesToQueue('oro.leftover');
        self::assertStringContainsString('oro.leftover', self::getMessageQueueStateDump());

        self::purgeMessageQueue();

        self::assertStringContainsString('the message queue is empty', self::getMessageQueueStateDump());
    }

    /**
     * Runs the consumption and returns the failure it is expected to raise. The failure is captured rather than
     * asserted inside a try block so that a regression of the guard is reported as such, instead of being swallowed
     * by the very catch that is meant to receive the expected failure.
     */
    private function captureConsumeAllMessagesFailure(): AssertionFailedError
    {
        $error = null;
        try {
            $this->consumeAllMessages();
        } catch (AssertionFailedError $e) {
            $error = $e;
        }

        self::assertNotNull(
            $error,
            'Consuming all messages was expected to give up once the budget was exceeded, but it returned normally.'
        );

        return $error;
    }

    private static function claimAllQueuedMessages(string $consumerId): void
    {
        $dbal = self::requireMessageQueueDbalConnection()->getDBALConnection();
        $tableName = $dbal->quoteIdentifier(self::requireMessageQueueDbalConnection()->getTableName());

        $dbal->executeStatement(
            'UPDATE ' . $tableName . ' SET consumer_id = :consumerId',
            ['consumerId' => $consumerId],
            ['consumerId' => Types::STRING]
        );
    }

    private static function moveAllQueuedMessagesToQueue(string $queueName): void
    {
        $dbal = self::requireMessageQueueDbalConnection()->getDBALConnection();
        $tableName = $dbal->quoteIdentifier(self::requireMessageQueueDbalConnection()->getTableName());

        $dbal->executeStatement(
            'UPDATE ' . $tableName . ' SET queue = :queueName',
            ['queueName' => $queueName],
            ['queueName' => Types::STRING]
        );
    }

    private static function requireMessageQueueDbalConnection(): DbalConnection
    {
        $connection = self::getMessageQueueDbalConnection();
        self::assertNotNull($connection, 'This test requires the DBAL message queue transport.');

        return $connection;
    }
}
