<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityRepository;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function messageBodyLoggerCriticalDataProvider()
    {
        return [
            [
                '[ExportMessageProcessor] Got invalid message: "{"processorAlias":"alias","userId":1}"',
                ['processorAlias' => 'alias', 'userId' => 1],
            ],
            [
                '[ExportMessageProcessor] Got invalid message: "{"jobName":"name","userId":1}"',
                ['jobName' => 'name', 'userId' => 1],
            ],
            [
                '[ExportMessageProcessor] Got invalid message: "{"jobName":"name","processorAlias":"alias"}"',
                ['jobName' => 'name', 'processorAlias' => 'alias'],
            ],
        ];
    }

    /**
     * @dataProvider messageBodyLoggerCriticalDataProvider
     */
    public function testShouldRejectMessageAndLogCriticalIfRequiredParametersAreMissing($loggerMessage, $messageBody)
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($loggerMessage)
        ;

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createConfigManagerMock(),
            $this->createDoctrineHelperMock(),
            $this->createSecurityFacadeMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
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
            $this->createMessageProducerInterfaceMock(),
            $this->createConfigManagerMock(),
            $doctrineHelper,
            $this->createSecurityFacadeMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
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
    private function createMessageProducerInterfaceMock()
    {
        return $this->getMock(MessageProducerInterface::class);
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
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionInterfaceMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    private function createSecurityFacadeMock()
    {
        return $this->getMock(SecurityFacade::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageInterfaceMock()
    {
        return $this->getMock(TokenStorageInterface::class);
    }
}
