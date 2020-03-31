<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport;

use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MessageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['body', 'message body', ''],
            ['properties', ['propertyKey' => 'value'], []],
            ['headers', ['headerKey' => 'value'], []],
            ['redelivered', true, false],
        ];

        $message = new Message();
        $this->assertPropertyAccessors($message, $properties);
    }

    public function testCorrelationId(): void
    {
        $message = new Message();
        $message->setHeaders(['headerKey' => 'value']);
        $message->setCorrelationId('correlation id');

        $this->assertEquals([
            'headerKey' => 'value',
            'correlation_id' => 'correlation id',
        ], $message->getHeaders());
        $this->assertEquals('correlation id', $message->getCorrelationId());
    }

    public function testMessageId(): void
    {
        $message = new Message();
        $message->setHeaders(['headerKey' => 'value']);
        $message->setMessageId('message id');

        $this->assertEquals([
            'headerKey' => 'value',
            'message_id' => 'message id',
        ], $message->getHeaders());
        $this->assertEquals('message id', $message->getMessageId());
    }

    public function testTimestamp(): void
    {
        $message = new Message();
        $message->setHeaders(['headerKey' => 'value']);
        $message->setTimestamp(123);

        $this->assertEquals([
            'headerKey' => 'value',
            'timestamp' => 123,
        ], $message->getHeaders());
        $this->assertEquals(123, $message->getTimestamp());
    }

    public function testPriority(): void
    {
        $message = new Message();
        $message->setHeaders(['headerKey' => 'value']);
        $message->setPriority(4);

        $this->assertEquals([
            'headerKey' => 'value',
            'priority' => 4,
        ], $message->getHeaders());
        $this->assertEquals(4, $message->getPriority());
    }

    public function testDelay(): void
    {
        $message = new Message();
        $message->setProperties(['propertyKey' => 'value']);
        $message->setDelay(10);

        $this->assertEquals([
            'propertyKey' => 'value',
            'delay' => 10,
        ], $message->getProperties());
        $this->assertEquals(10, $message->getDelay());
    }
}
