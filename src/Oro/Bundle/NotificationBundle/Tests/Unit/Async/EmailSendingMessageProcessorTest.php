<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\NotificationBundle\Async\EmailSendingMessageProcessor;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class EmailSendingMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldConstructWithRequiredArguments()
    {
        new EmailSendingMessageProcessor($this->createMailerMock(), $this->createLoggerMock());
    }

    public function testShouldRejectIfSenderNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[EmailSendingMessageProcessor] Empty email sender field: "{"key":"value"}"')
        ;

        $processor = new EmailSendingMessageProcessor(
            $this->createMailerMock(),
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode(['key' => 'value']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfSenderNotArray()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[EmailSendingMessageProcessor] Empty email sender field: "{"from":"not array"}"')
        ;

        $processor = new EmailSendingMessageProcessor(
            $this->createMailerMock(),
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode(['from' => 'not array']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfRecepientNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[EmailSendingMessageProcessor] Empty email receiver field: "{"from":["sender"]}"')
        ;

        $processor = new EmailSendingMessageProcessor(
            $this->createMailerMock(),
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode(['from' => ['sender']]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfBodyNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[EmailSendingMessageProcessor] Empty email body field: "{"from":["sender"],"to":"receiver"}"')
        ;

        $processor = new EmailSendingMessageProcessor(
            $this->createMailerMock(),
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode(['from' => ['sender'], 'to' => 'receiver']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfBodyNotArray()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                '[EmailSendingMessageProcessor] '.
                'Empty email body field: "{"from":["sender"],"to":"receiver","body":"some body"}"')
        ;

        $processor = new EmailSendingMessageProcessor(
            $this->createMailerMock(),
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode(['from' => ['sender'], 'to' => 'receiver', 'body' => 'some body']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfFailedRecepient()
    {
        $failedRecepient = 'failed@email.com';

        $mailer = $this->createMailerMock();
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($message, &$recepients) use ($failedRecepient) {
                $recepients[] = $failedRecepient;
            })
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[EmailSendingMessageProcessor] Empty email receiver field: "{"key":"value"}"')
        ;


        $message = new NullMessage;
        $message->setBody(json_encode(['key' => 'value']));
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | DirectMailer
     */
    private function createMailerMock()
    {
        return $this
            ->getMockBuilder(DirectMailer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }
}
