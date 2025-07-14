<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Processor;

use Oro\Component\MessageQueue\Log\Processor\RestoreOriginalChannelProcessor;
use PHPUnit\Framework\TestCase;

class RestoreOriginalChannelProcessorTest extends TestCase
{
    private RestoreOriginalChannelProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new RestoreOriginalChannelProcessor();
    }

    public function testCouldBeInvokedWithoutSavedOriginalLogChannel(): void
    {
        $this->assertEquals(
            ['message' => 'test', 'context' => []],
            call_user_func($this->processor, ['message' => 'test', 'context' => []])
        );
    }

    public function testShouldMoveSavedOriginalLogChannel(): void
    {
        $this->assertEquals(
            ['message' => 'test', 'context' => [], 'channel' => 'test_channel'],
            call_user_func($this->processor, ['message' => 'test', 'context' => ['log_channel' => 'test_channel']])
        );
    }
}
