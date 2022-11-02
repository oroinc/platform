<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Component\MessageQueue\Client\CallbackMessageBuilder;

class MessageBufferTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageBuffer */
    private $buffer;

    protected function setUp(): void
    {
        $this->buffer = new MessageBuffer();
    }

    public function testEmpty()
    {
        self::assertFalse($this->buffer->hasMessages());
        self::assertSame([], $this->buffer->getMessages());
        self::assertNull($this->buffer->getMessage(0));
        self::assertSame([], $this->buffer->getTopics());
        self::assertFalse($this->buffer->hasMessagesForTopic('topic1'));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopic('topic1')));
        self::assertFalse($this->buffer->hasMessagesForTopics(['topic1', 'topic2']));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopics(['topic1', 'topic2'])));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuffer()
    {
        // test addMessage()
        $this->buffer->addMessage('topic1', ['msg1']);
        $this->buffer->addMessage('topic1', ['msg2']);
        $this->buffer->addMessage('topic2', ['msg3']);
        self::assertTrue($this->buffer->hasMessages());
        self::assertSame(
            [0 => ['topic1', ['msg1']], 1 => ['topic1', ['msg2']], 2 => ['topic2', ['msg3']]],
            $this->buffer->getMessages()
        );
        self::assertSame(['msg1'], $this->buffer->getMessage(0));
        self::assertSame(['msg2'], $this->buffer->getMessage(1));
        self::assertSame(['msg3'], $this->buffer->getMessage(2));
        self::assertNull($this->buffer->getMessage(3));
        self::assertSame(['topic1', 'topic2'], $this->buffer->getTopics());
        self::assertTrue($this->buffer->hasMessagesForTopic('topic1'));
        self::assertSame(
            [0 => ['msg1'], 1 => ['msg2']],
            iterator_to_array($this->buffer->getMessagesForTopic('topic1'))
        );
        self::assertTrue($this->buffer->hasMessagesForTopic('topic2'));
        self::assertSame(
            [2 => ['msg3']],
            iterator_to_array($this->buffer->getMessagesForTopic('topic2'))
        );
        self::assertFalse($this->buffer->hasMessagesForTopic('topic3'));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopic('topic3')));
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic1', 'topic2', 'topic3']));
        self::assertSame(
            [0 => ['topic1', ['msg1']], 1 => ['topic1', ['msg2']], 2 => ['topic2', ['msg3']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1', 'topic2', 'topic3']))
        );
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic1']));
        self::assertSame(
            [0 => ['topic1', ['msg1']], 1 => ['topic1', ['msg2']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1']))
        );
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic2', 'topic3']));
        self::assertSame(
            [2 => ['topic2', ['msg3']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic2', 'topic3']))
        );
        self::assertFalse($this->buffer->hasMessagesForTopics(['topic3', 'topic4']));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopics(['topic3', 'topic4'])));

        // test removeMessage()
        $this->buffer->removeMessage(0);
        self::assertTrue($this->buffer->hasMessages());
        self::assertSame(
            [1 => ['topic1', ['msg2']], 2 => ['topic2', ['msg3']]],
            $this->buffer->getMessages()
        );
        self::assertNull($this->buffer->getMessage(0));
        self::assertSame(['msg2'], $this->buffer->getMessage(1));
        self::assertSame(['msg3'], $this->buffer->getMessage(2));
        self::assertSame(['topic1', 'topic2'], $this->buffer->getTopics());
        self::assertTrue($this->buffer->hasMessagesForTopic('topic1'));
        self::assertSame([1 => ['msg2']], iterator_to_array($this->buffer->getMessagesForTopic('topic1')));
        self::assertTrue($this->buffer->hasMessagesForTopic('topic2'));
        self::assertSame([2 => ['msg3']], iterator_to_array($this->buffer->getMessagesForTopic('topic2')));
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic1', 'topic2', 'topic3']));
        self::assertSame(
            [1 => ['topic1', ['msg2']], 2 => ['topic2', ['msg3']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1', 'topic2', 'topic3']))
        );
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic1']));
        self::assertSame(
            [1 => ['topic1', ['msg2']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1']))
        );

        // test replaceMessage()
        $this->buffer->replaceMessage(2, ['msg3_new']);
        self::assertTrue($this->buffer->hasMessages());
        self::assertSame(
            [1 => ['topic1', ['msg2']], 2 => ['topic2', ['msg3_new']]],
            $this->buffer->getMessages()
        );
        self::assertNull($this->buffer->getMessage(0));
        self::assertSame(['msg2'], $this->buffer->getMessage(1));
        self::assertSame(['msg3_new'], $this->buffer->getMessage(2));
        self::assertSame(['topic1', 'topic2'], $this->buffer->getTopics());
        self::assertTrue($this->buffer->hasMessagesForTopic('topic1'));
        self::assertSame([1 => ['msg2']], iterator_to_array($this->buffer->getMessagesForTopic('topic1')));
        self::assertTrue($this->buffer->hasMessagesForTopic('topic2'));
        self::assertSame([2 => ['msg3_new']], iterator_to_array($this->buffer->getMessagesForTopic('topic2')));
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic1', 'topic2', 'topic3']));
        self::assertSame(
            [1 => ['topic1', ['msg2']], 2 => ['topic2', ['msg3_new']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1', 'topic2', 'topic3']))
        );

        // test removeMessage() for the last message for a topic
        $this->buffer->removeMessage(2);
        self::assertTrue($this->buffer->hasMessages());
        self::assertSame(
            [1 => ['topic1', ['msg2']]],
            $this->buffer->getMessages()
        );
        self::assertNull($this->buffer->getMessage(0));
        self::assertSame(['msg2'], $this->buffer->getMessage(1));
        self::assertNull($this->buffer->getMessage(2));
        self::assertSame(['topic1'], $this->buffer->getTopics());
        self::assertTrue($this->buffer->hasMessagesForTopic('topic1'));
        self::assertSame([1 => ['msg2']], iterator_to_array($this->buffer->getMessagesForTopic('topic1')));
        self::assertFalse($this->buffer->hasMessagesForTopic('topic2'));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopic('topic2')));
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic1', 'topic2', 'topic3']));
        self::assertSame(
            [1 => ['topic1', ['msg2']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1', 'topic2', 'topic3']))
        );
        self::assertTrue($this->buffer->hasMessagesForTopics(['topic1']));
        self::assertSame(
            [1 => ['topic1', ['msg2']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1']))
        );
        self::assertFalse($this->buffer->hasMessagesForTopics(['topic2']));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopics(['topic2'])));

        // test removeMessage() for the last message in the buffer
        $this->buffer->removeMessage(1);
        self::assertFalse($this->buffer->hasMessages());
        self::assertSame([], $this->buffer->getMessages());
        self::assertNull($this->buffer->getMessage(0));
        self::assertNull($this->buffer->getMessage(1));
        self::assertNull($this->buffer->getMessage(2));
        self::assertSame([], $this->buffer->getTopics());
        self::assertFalse($this->buffer->hasMessagesForTopic('topic1'));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopic('topic1')));
        self::assertFalse($this->buffer->hasMessagesForTopics(['topic1', 'topic2']));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopics(['topic1', 'topic2'])));
    }

    public function testClear()
    {
        $this->buffer->addMessage('topic1', ['message1']);
        $this->buffer->clear();

        self::assertFalse($this->buffer->hasMessages());
        self::assertSame([], $this->buffer->getMessages());
        self::assertNull($this->buffer->getMessage(0));
        self::assertSame([], $this->buffer->getTopics());
        self::assertFalse($this->buffer->hasMessagesForTopic('topic1'));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopic('topic1')));
        self::assertFalse($this->buffer->hasMessagesForTopics(['topic1']));
        self::assertSame([], iterator_to_array($this->buffer->getMessagesForTopics(['topic1'])));
    }

    public function testGetMessagesWhenMessageRepresentsByMessageBuilder()
    {
        $this->buffer->addMessage(
            'topic1',
            new CallbackMessageBuilder(function () {
                return ['message1'];
            })
        );
        self::assertSame([0 => ['topic1', ['message1']]], $this->buffer->getMessages());
    }

    public function testGetMessageWhenMessageRepresentsByMessageBuilder()
    {
        $this->buffer->addMessage(
            'topic1',
            new CallbackMessageBuilder(function () {
                return ['message1'];
            })
        );
        self::assertSame(['message1'], $this->buffer->getMessage(0));
    }

    public function testGetMessagesForTopicWhenMessageRepresentsByMessageBuilder()
    {
        $this->buffer->addMessage(
            'topic1',
            new CallbackMessageBuilder(function () {
                return ['message1'];
            })
        );
        self::assertSame(
            [0 => ['message1']],
            iterator_to_array($this->buffer->getMessagesForTopic('topic1'))
        );
    }

    public function testGetMessagesForTopicsWhenMessageRepresentsByMessageBuilder()
    {
        $this->buffer->addMessage(
            'topic1',
            new CallbackMessageBuilder(function () {
                return ['message1'];
            })
        );
        self::assertSame(
            [0 => ['topic1', ['message1']]],
            iterator_to_array($this->buffer->getMessagesForTopics(['topic1']))
        );
    }

    public function testAddMessageRepresentsByMessageBuilderWhenBufferIsAlreadyResolved()
    {
        $this->buffer->addMessage('topic1', ['message1']);
        $this->buffer->addMessage(
            'topic1',
            new CallbackMessageBuilder(function () {
                return ['message2'];
            })
        );
        self::assertSame(
            [0 => ['topic1', ['message1']], 1 => ['topic1', ['message2']]],
            $this->buffer->getMessages()
        );

        $this->buffer->addMessage(
            'topic1',
            new CallbackMessageBuilder(function () {
                return ['message3'];
            })
        );
        self::assertSame(
            [0 => ['topic1', ['message1']], 1 => ['topic1', ['message2']], 2 => ['topic1', ['message3']]],
            $this->buffer->getMessages()
        );
    }
}
