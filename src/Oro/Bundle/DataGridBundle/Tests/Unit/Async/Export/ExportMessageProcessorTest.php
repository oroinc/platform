<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\DataGridBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT],
            ExportMessageProcessor::getSubscribedTopics()
        );
    }

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
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($loggerMessage)
        ;

        $processor = $this->createExportMessageProcessorStub(['logger' => $logger]);

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfWriterNotFound()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[DataGridExportMessageProcessor] Invalid format: "csv"')
        ;

        $writerChain = $this->createWriterChainMock();
        $writerChain
            ->expects($this->once())
            ->method('getWriter')
            ->with('csv')
            ->willReturn(null)
        ;

        $processor = $this->createExportMessageProcessorStub([
            'logger' => $logger,
            'writerChain' => $writerChain,
        ]);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobId' => 1,
            'securityToken' => 'test',
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv'
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfWriterNotInstanceOfFileWriter()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[DataGridExportMessageProcessor] Invalid format: "csv"')
        ;

        $writerChain = $this->createWriterChainMock();
        $writerChain
            ->expects($this->once())
            ->method('getWriter')
            ->with('csv')
            ->willReturn($this->createMock(ItemWriterInterface::class))
        ;

        $processor = $this->createExportMessageProcessorStub([
            'logger' => $logger,
            'writerChain' => $writerChain,
        ]);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobId' => 1,
            'securityToken' => 'test',
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv'
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfSecurityTokenCannotBeSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Cannot set security token'))
        ;

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('serialized_security_token'))
            ->willReturn(null)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->never())
            ->method('setToken')
        ;

        $writerChain = $this->createWriterChainMock();
        $writerChain
            ->expects($this->once())
            ->method('getWriter')
            ->with($this->equalTo('csv'))
            ->willReturn($this->createFileStreamWriterMock())
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobId' => 1,
            'securityToken' => 'serialized_security_token',
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv',
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createDatagridExportConnectorMock(),
            $this->createExportProcessorMock(),
            $writerChain,
            $tokenStorage,
            $this->createJobStorageMock(),
            $logger
        );
        $processor->setTokenSerializer($tokenSerializer);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function exportHandlerResultProvider()
    {
        return [
            [
                ['success' => false],
                'Export result. Success: No.',
                ExportMessageProcessor::REJECT,
            ],
            [
                ['success' => true],
                'Export result. Success: Yes.',
                ExportMessageProcessor::ACK,
            ],
        ];
    }

    /**
     * @dataProvider exportHandlerResultProvider
     *
     * @param array $handleResult
     * @param string $loggerMessage
     * @param string $expectedResult
     */
    public function testShouldRejectMessageIfExportResultIsNotSuccessful($handleResult, $loggerMessage, $expectedResult)
    {
        $user = new User();
        $user->setId(1);

        $token = $this->createTokenMock();
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($this->equalTo('serialized_security_token'))
            ->willReturn($token)
        ;

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $writerChain = $this->createWriterChainMock();
        $writerChain
            ->expects($this->once())
            ->method('getWriter')
            ->with($this->equalTo('csv'))
            ->willReturn($this->createFileStreamWriterMock())
        ;

        $job = new Job();

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->will($this->returnCallback(function ($jobId, $callback) use ($jobRunner, $job) {
                return $callback($jobRunner, $job);
            }))
        ;

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($handleResult)
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains($loggerMessage))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobId' => 1,
            'securityToken' => 'serialized_security_token',
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv',
        ]));

        $processor = new ExportMessageProcessor(
            $exportHandler,
            $jobRunner,
            $this->createDatagridExportConnectorMock(),
            $this->createExportProcessorMock(),
            $writerChain,
            $tokenStorage,
            $jobStorage,
            $logger
        );
        $processor->setTokenSerializer($tokenSerializer);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportMessageProcessor
     */
    private function createExportMessageProcessorStub(array $arguments = [])
    {
        $arguments = array_merge(
            [
                'exportHandler' => $this->createExportHandlerMock(),
                'jobRunner' => $this->createJobRunnerMock(),
                'exportConnector' => $this->createDatagridExportConnectorMock(),
                'exportProcessor' => $this->createExportProcessorMock(),
                'writerChain' => $this->createWriterChainMock(),
                'tokenStorage' => $this->createTokenStorageMock(),
                'jobStorage' => $this->createJobStorageMock(),
                'logger' => $this->createLoggerMock()
            ],
            $arguments
        );

        return $this
            ->getMockBuilder(ExportMessageProcessor::class)
            ->setMethods(['getSubscribedTopics'])
            ->setConstructorArgs($arguments)
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WriterChain
     */
    private function createWriterChainMock()
    {
        return $this->createMock(WriterChain::class);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridExportConnector
     */
    private function createDatagridExportConnectorMock()
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
    private function createTokenStorageMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileStreamWriter
     */
    private function createFileStreamWriterMock()
    {
        return $this->createMock(FileStreamWriter::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    private function createTokenMock()
    {
        return $this->createMock(TokenInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenSerializerInterface
     */
    private function createTokenSerializerMock()
    {
        return $this->createMock(TokenSerializerInterface::class);
    }
}
