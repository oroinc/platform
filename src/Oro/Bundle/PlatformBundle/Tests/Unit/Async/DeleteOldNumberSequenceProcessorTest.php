<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Async;

use Oro\Bundle\PlatformBundle\Async\DeleteOldNumberSequenceProcessor;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteOldNumberSequenceTopic;
use Oro\Bundle\PlatformBundle\Event\DeleteOldNumberSequenceEvent;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteOldNumberSequenceProcessorTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private DeleteOldNumberSequenceProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->processor = new DeleteOldNumberSequenceProcessor($this->eventDispatcher);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [DeleteOldNumberSequenceTopic::getName()],
            DeleteOldNumberSequenceProcessor::getSubscribedTopics()
        );
    }

    public function testProcessValidMessage(): void
    {
        $messageBody = [
            'sequenceType' => 'order',
            'discriminatorType' => 'regular'
        ];
        $message = new Message();
        $message->setBody($messageBody);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (DeleteOldNumberSequenceEvent $event) use ($messageBody) {
                    return $event->getSequenceType() === $messageBody['sequenceType'] &&
                        $event->getDiscriminatorType() === $messageBody['discriminatorType'];
                })
            );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
