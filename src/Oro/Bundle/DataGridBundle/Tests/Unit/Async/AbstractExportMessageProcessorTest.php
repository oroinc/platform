<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityRepository;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Async\AbstractExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class AbstractExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function messageBodyLoggerCriticalDataProvider()
    {
        return [
            [
                '[DataGridExportMessageProcessor] Got invalid message: '
                    .'"{"parameters":{"gridName":"grid_name"},"format":"csv"}"',
                ['parameters' => ['gridName' => 'grid_name'], 'format' => 'csv'],
            ],
            [
                '[DataGridExportMessageProcessor] Got invalid message: '
                    .'"{"userId":1,"parameters":"must_be_array","format":"csv"}"',
                ['userId' => 1, 'parameters' => 'must_be_array', 'format' => 'csv'],
            ],
            [
                '[DataGridExportMessageProcessor] Got invalid message: '
                    .'"{"userId":1,"parameters":{"not_gridName":"value"},"format":"csv"}"',
                ['userId' => 1, 'parameters' => ['not_gridName' => 'value'], 'format' => 'csv'],
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

        $processor = $this->createStubAbstractMessageProcessor(['logger' => $logger]);

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(AbstractExportMessageProcessor::REJECT, $result);
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
            ->with('[DataGridExportMessageProcessor] Cannot find user by id "1"')
        ;

        $processor = $this->createStubAbstractMessageProcessor([
            'doctrineHelper' => $doctrineHelper,
            'logger' => $logger
        ]);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'userId' => 1,
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv'
        ]));

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(AbstractExportMessageProcessor::REJECT, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractExportMessageProcessor
     */
    private function createStubAbstractMessageProcessor(array $arguments = [])
    {
        $arguments = array_merge(
            [
                'exportHandler' => $this->createExportHandlerMock(),
                'jobRunner' => $this->createJobRunnerMock(),
                'messageProducer' => $this->createMessageProducerInterfaceMock(),
                'configManager' => $this->createConfigManagerMock(),
                'doctrineHelper' => $this->createDoctrineHelperMock(),
                'exportConnector' => $this->createExportConnectorMock(),
                'exportProcessor' => $this->createExportProcessorMock(),
                'tokenStorage' => $this->createTokenStorageInterfaceMock(),
                'logger' => $this->createLoggerInterfaceMock()
            ],
            $arguments
        );

        return $this->getMock(AbstractExportMessageProcessor::class, ['getSubscribedTopics'], $arguments);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridExportConnector
     */
    private function createExportConnectorMock()
    {
        return $this->getMock(DatagridExportConnector::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportProcessor
     */
    private function createExportProcessorMock()
    {
        return $this->getMock(ExportProcessor::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageInterfaceMock()
    {
        return $this->getMock(TokenStorageInterface::class);
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
}