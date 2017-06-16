<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Async\ExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;

use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;

use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\CsvFileWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
                    .'"{"securityToken":"test_string","parameters":"must_be_array","format":"csv"}"',
                ['securityToken' => 'test_string', 'parameters' => 'must_be_array', 'format' => 'csv'],
            ],
            [
                '[DataGridExportMessageProcessor] Got invalid message: '
                    .'"{"securityToken":"test_string","parameters":{"not_gridName":"value"},"format":"csv"}"',
                ['securityToken' => 'test_string', 'parameters' => ['not_gridName' => 'value'], 'format' => 'csv'],
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

        $processor = $this->createExportMessageProcessorStab(['logger' => $logger]);

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfWriterNotFound()
    {
        $doctrineHelper = $this->createDoctrineHelperMock();

        $logger = $this->createLoggerInterfaceMock();
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

        $processor = $this->createExportMessageProcessorStab([
            'doctrineHelper' => $doctrineHelper,
            'logger' => $logger,
            'writerChain' => $writerChain,
        ]);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'securityToken' => 'test_token',
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv'
        ]));

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfWriterNotInstanceOfFileWriter()
    {
        $doctrineHelper = $this->createDoctrineHelperMock();

        $logger = $this->createLoggerInterfaceMock();
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

        $processor = $this->createExportMessageProcessorStab([
            'doctrineHelper' => $doctrineHelper,
            'logger' => $logger,
            'writerChain' => $writerChain,
        ]);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'securityToken' => 'test_token',
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv'
        ]));

        $result = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportMessageProcessor
     */
    private function createExportMessageProcessorStab(array $arguments = [])
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
                'writerChain' => $this->createWriterChainMock(),
                'tokenStorage' => $this->createTokenStorageInterfaceMock(),
                'logger' => $this->createLoggerInterfaceMock(),
                'renderer' => $this->createRenderMock(),
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
     * @return \PHPUnit_Framework_MockObject_MockObject | CsvFileWriter
     */
    private function createCsvWriterMock()
    {
        return $this->getMockBuilder(CsvFileWriter::class)->disableOriginalConstructor()->getMock();
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EmailRenderer
     */
    private function createRenderMock()
    {
        return $this->createMock(EmailRenderer::class);
    }
}
