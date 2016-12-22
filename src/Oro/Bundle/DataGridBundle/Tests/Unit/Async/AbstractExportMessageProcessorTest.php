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
        $repository = $this->createMock(EntityRepository::class);
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

        return $this
            ->getMockBuilder(AbstractExportMessageProcessor::class)
            ->setMethods(['getSubscribedTopics'])
            ->setConstructorArgs($arguments)
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->createMock(ExportHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerInterfaceMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this->createMock(ConfigManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperMock()
    {
        return $this->createMock(DoctrineHelper::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridExportConnector
     */
    private function createExportConnectorMock()
    {
        return $this->createMock(DatagridExportConnector::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportProcessor
     */
    private function createExportProcessorMock()
    {
        return $this->createMock(ExportProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageInterfaceMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerInterfaceMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionInterfaceMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
