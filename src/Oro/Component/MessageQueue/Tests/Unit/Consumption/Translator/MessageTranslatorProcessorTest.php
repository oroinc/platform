<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Translator;

use Oro\Component\MessageQueue\Consumption\Translator\MessageTranslatorProcessor;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullTopic;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class MessageTranslatorProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new MessageTranslatorProcessor('topic-name');
    }

    public function testShouldCreateNewMessageAndPutlishItToTopic()
    {
        $body = 'body';
        $headers = [
            'header' => 'value',
        ];
        $properties = [
            'property' => 'value'
        ];

        $message = new NullMessage();
        $message->setBody($body);
        $message->setHeaders($headers);
        $message->setProperties($properties);

        $newMessage = new NullMessage();

        $topic = new NullTopic('');

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($newMessage))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->identicalTo($body), $this->identicalTo($properties), $this->identicalTo($headers))
            ->will($this->returnValue($newMessage))
        ;
        $session
            ->expects($this->once())
            ->method('createTopic')
            ->with('topic-name')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($producer))
        ;

        $translator = new MessageTranslatorProcessor('topic-name');
        $translator->process($message, $session);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }
}
