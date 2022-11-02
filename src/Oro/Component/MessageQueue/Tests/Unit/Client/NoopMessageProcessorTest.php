<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\NoopMessageProcessor;
use Oro\Component\MessageQueue\Exception\MessageProcessorNotSpecifiedException;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class NoopMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        self::assertEquals(
            'sample_status',
            (new NoopMessageProcessor('sample_status'))
                ->process(new Message(), $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessThrowsException(): void
    {
        $this->expectExceptionObject(MessageProcessorNotSpecifiedException::create());

        (new NoopMessageProcessor(NoopMessageProcessor::THROW_EXCEPTION))
            ->process(new Message(), $this->createMock(SessionInterface::class));
    }
}
