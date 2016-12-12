<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Async\AutoResponseMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class AutoResponseMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AutoResponseMessageProcessor(
            $this->createDoctrineMock(),
            $this->createAutoResponseManagerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfBodyIsInvalid()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[AutoResponseMessageProcessor] Got invalid message. "{"key":"value"}"')
        ;

        $processor = new AutoResponseMessageProcessor(
            $this->createDoctrineMock(),
            $this->createAutoResponseManagerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode(['key' => 'value']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendAutoResponse()
    {
        $email = new Email();

        $repository = $this->createEntityRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->will($this->returnValue($email))
        ;

        $doctrine  = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(Email::class)
            ->will($this->returnValue($repository))
        ;

        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->once())
            ->method('sendAutoResponses')
            ->with($this->identicalTo($email))
        ;

        $processor = new AutoResponseMessageProcessor(
            $doctrine,
            $autoResponseManager,
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 123]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRejectMessageIfEmailWasNotFound()
    {
        $repository = $this->createEntityRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->will($this->returnValue(null))
        ;

        $doctrine  = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(Email::class)
            ->will($this->returnValue($repository))
        ;

        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->never())
            ->method('sendAutoResponses')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('[AutoResponseMessageProcessor] Email was not found. id: "123"')
        ;

        $processor = new AutoResponseMessageProcessor(
            $doctrine,
            $autoResponseManager,
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 123]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::SEND_AUTO_RESPONSE], AutoResponseMessageProcessor::getSubscribedTopics());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AutoResponseManager
     */
    private function createAutoResponseManagerMock()
    {
        return $this->getMock(AutoResponseManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    private function createDoctrineMock()
    {
        return $this->getMock(Registry::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    private function createEntityRepositoryMock()
    {
        return $this->getMock(EntityRepository::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
