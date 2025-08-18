<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Extension;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Async\ChangeConfigProcessor;
use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\ChangeConfigTestTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;

class InterruptConsumptionExtensionTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use MessageQueueExtension;

    private ?string $initialTimezone;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue();

        $this->initialTimezone = self::getConfigManager(null)->get('oro_locale.timezone');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager(null);
        if ($configManager->get('oro_locale.timezone') !== $this->initialTimezone) {
            $configManager->set('oro_locale.timezone', $this->initialTimezone);
            $configManager->flush();
            $configManager->reload();
        }

        self::purgeMessageQueue();
    }

    public function testMessageConsumptionIsInterruptedByMessageLimit(): void
    {
        self::sendMessage(
            ChangeConfigTestTopic::getName(),
            ['message' => ChangeConfigProcessor::COMMAND_NOOP]
        );
        self::sendMessage(
            ChangeConfigTestTopic::getName(),
            ['message' => ChangeConfigProcessor::COMMAND_NOOP]
        );

        self::getConsumer()
            ->bind('oro.default')
            ->consume(new ChainExtension([new LimitConsumedMessagesExtension(2)]));

        $this->assertInterruptionMessage(
            'Consuming interrupted. Queue: "oro.default", reason: "The message limit reached."'
        );
    }

    public function testMessageConsumptionIsInterruptedByConfigCacheChanged(): void
    {
        self::sendMessage(
            ChangeConfigTestTopic::getName(),
            ['message' => ChangeConfigProcessor::COMMAND_CHANGE_CACHE]
        );
        self::sendMessage(
            ChangeConfigTestTopic::getName(),
            ['message' => ChangeConfigProcessor::COMMAND_CHANGE_CACHE]
        );

        self::getConsumer()
            ->bind('oro.default')
            ->consume(new ChainExtension([new LimitConsumedMessagesExtension(2)]));

        $this->assertInterruptionMessage(
            'Consuming interrupted. Queue: "oro.default", reason: "The cache has changed."'
        );
    }

    private function assertInterruptionMessage(string $expectedMessage): void
    {
        self::assertTrue(self::getLoggerTestHandler()->hasRecord($expectedMessage, 'warning'));
    }
}
