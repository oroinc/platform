<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test\Unit;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;

class MessageQueueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    public function testShouldAllowGetMessageCollector()
    {
        self::assertSame(self::$messageCollector, self::getMessageCollector());
    }

    public function testShouldSentMessagesBeEmptyInEachTest()
    {
        self::assertCount(0, self::getSentMessages());
    }

    public function testShouldAllowGetSentMessages()
    {
        $topic = 'test topic';
        $message = 'test message';

        self::getMessageProducer()->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            self::getSentMessages()
        );
    }
}
