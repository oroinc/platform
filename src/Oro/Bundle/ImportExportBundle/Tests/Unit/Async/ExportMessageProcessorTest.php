<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityRepository;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createConfigManagerMock(),
            $this->createDoctrineHelperMock(),
            $this->createLoggerInterfaceMock()
        );
    }

    public function testShouldRejectMessageAndLogCriticalIfJobNameIsMissing()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Got invalid message: "{"processorAlias":"alias","userId":1}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'processorAlias' => 'alias',
            'userId' => 1
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createConfigManagerMock(),
            $this->createDoctrineHelperMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfProcessorAliasIsMissing()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Got invalid message: "{"jobName":"name","userId":1}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName' => 'name',
            'userId' => 1,
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createConfigManagerMock(),
            $this->createDoctrineHelperMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfUserIdIsMissing()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Got invalid message: "{"jobName":"name","processorAlias":"alias"}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName' => 'name',
            'processorAlias' => 'alias',
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createConfigManagerMock(),
            $this->createDoctrineHelperMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfUserNotFound()
    {
        $repository = $this->getMock(EntityRepository::class, [], [], '', false);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null)
        ;

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($repository)
        ;

        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Cannot find user by id "1"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName' => 'name',
            'processorAlias' => 'alias',
            'userId' => 1,
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createConfigManagerMock(),
            $doctrineHelper,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT],
            ExportMessageProcessor::getSubscribedTopics()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->getMock(ExportHandler::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMock(JobRunner::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->getMock(ConfigManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperMock()
    {
        return $this->getMock(DoctrineHelper::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerInterfaceMock()
    {
        return $this->getMock(LoggerInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }
}
