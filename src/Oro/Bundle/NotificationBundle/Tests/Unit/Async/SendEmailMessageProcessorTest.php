<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SendEmailMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldConstructWithRequiredArguments()
    {
        new SendEmailMessageProcessor($this->createMailerMock(), $this->createLoggerMock());
    }

    public function testShouldBeSubscribedForTopics()
    {
        $expectedSubscribedTopics = [
            Topics::SEND_NOTIFICATION_EMAIL,
        ];

        $this->assertEquals($expectedSubscribedTopics, SendEmailMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectIfSenderNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[SendEmailMessageProcessor] Got invalid message: "{"toEmail":"to@email.com"}"')
        ;

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode([
            'toEmail' => 'to@email.com'
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfRecepientNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[SendEmailMessageProcessor] Got invalid message: "{"fromEmail":"from@email.com"}"')
        ;

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode([
            'fromEmail' => 'from@email.com'
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfSendingFailed()
    {
        $mailer = $this->createMailerMock();
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturn(0)
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[SendEmailMessageProcessor] '.
                'Cannot send message: "{"toEmail":"to@email.com","fromEmail":"from@email.com"}"')
        ;

        $processor = new SendEmailMessageProcessor(
            $mailer,
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode([
            'toEmail' => 'to@email.com',
            'fromEmail' => 'from@email.com'
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendEmailAndReturmACKIfAllParametersCorrect()
    {
        $mailer = $this->createMailerMock();
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturn(1)
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $processor = new SendEmailMessageProcessor(
            $mailer,
            $logger
        );

        $message = new NullMessage;
        $message->setBody(json_encode([
            'toEmail' => 'to@email.com',
            'fromEmail' => 'from@email.com'
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
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
