<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor;

use Oro\Bundle\MessageQueueBundle\Log\Processor\RestoreOriginalChannelProcessor;

class RestoreOriginalChannelProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var RestoreOriginalChannelProcessor */
    private $processor;

    protected function setUp()
    {
        $this->processor = new RestoreOriginalChannelProcessor();
    }

    public function testCouldBeInvokedWithoutSavedOriginalLogChannel()
    {
        $this->assertEquals(
            ['message' => 'test', 'context' => []],
            call_user_func($this->processor, ['message' => 'test', 'context' => []])
        );
    }

    public function testShouldMoveSavedOriginalLogChannel()
    {
        $this->assertEquals(
            ['message' => 'test', 'context' => [], 'channel' => 'test_channel'],
            call_user_func($this->processor, ['message' => 'test', 'context' => ['log_channel' => 'test_channel']])
        );
    }
}
