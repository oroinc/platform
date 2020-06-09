<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Handler;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Log\Handler\ConsoleErrorHandler;
use Oro\Component\MessageQueue\Log\ConsumerState;

class ConsoleErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var ConsoleErrorHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->consumerState = new ConsumerState();

        $this->handler = new ConsoleErrorHandler($this->consumerState, new TestHandler(), Logger::CRITICAL);
    }

    public function testIsHandling()
    {
        $this->assertFalse($this->handler->isHandling([]));
        $this->consumerState->startConsumption();
        $this->assertTrue($this->handler->isHandling(['level' => Logger::CRITICAL]));
        $this->assertFalse($this->handler->isHandling(['level' => Logger::DEBUG]));
    }
}
