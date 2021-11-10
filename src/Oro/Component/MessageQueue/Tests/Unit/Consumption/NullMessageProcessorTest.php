<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\NullMessageProcessor;
use Oro\Component\MessageQueue\Exception\MessageProcessorNotFoundException;
use Oro\Component\MessageQueue\Exception\MessageProcessorNotSpecifiedException;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class NullMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWhenNoMessageProcessorName(): void
    {
        $this->expectExceptionObject(MessageProcessorNotSpecifiedException::create());

        (new NullMessageProcessor())->process(new Message(), $this->createMock(SessionInterface::class));
    }

    public function testProcess(): void
    {
        $messageProcessorName = 'sample_name';

        $this->expectExceptionObject(MessageProcessorNotFoundException::create($messageProcessorName));

        (new NullMessageProcessor($messageProcessorName))
            ->process(new Message(), $this->createMock(SessionInterface::class));
    }
}
