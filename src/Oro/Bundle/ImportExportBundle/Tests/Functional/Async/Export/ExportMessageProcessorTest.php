<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class ExportMessageProcessorTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer(): void
    {
        $instance = self::getContainer()->get('oro_importexport.async.export');

        self::assertInstanceOf(ExportMessageProcessor::class, $instance);
    }

    /**
     * @dataProvider exportProcessDataProvider
     */
    public function testShouldProcessExport(
        bool $resultSuccess,
        int $resultReadsCount,
        int $resultErrorsCount,
        string $expectedResult
    ): void {
        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:oro_test.add_or_replace:test_import_message'
        );
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );

        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody([
            'jobId' => $childJob->getId(),
            'jobName' => 'job_name',
            'processorAlias' => 'alias',
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'outputFormat' => 'csv',
            'outputFilePrefix' => null,
            'options' => [],
        ]);

        $exportResult = [
            'success' => $resultSuccess,
            'url' => 'http://localhost',
            'readsCount' => $resultReadsCount,
            'errorsCount' => $resultErrorsCount,
            'entities' => 'User',
        ];

        $exportHandler = $this->createMock(ExportHandler::class);
        $exportHandler->expects(self::once())
            ->method('getExportResult')
            ->with(
                $this->equalTo('job_name'),
                $this->equalTo('alias'),
                $this->equalTo(ProcessorRegistry::TYPE_EXPORT),
                $this->equalTo('csv'),
                $this->equalTo(null),
                $this->equalTo([])
            )
            ->willReturn($exportResult);

        self::getContainer()->set('oro_importexport.handler.export.stub', $exportHandler);

        $processor = self::getContainer()->get('oro_importexport.async.export');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals($expectedResult, $result);
        self::assertCount(5, $childJob->getData());
        self::assertEquals($exportResult, $childJob->getData());
    }

    public function exportProcessDataProvider(): array
    {
        return [
            [
                'resultSuccess' => true,
                'readsCount' => 100,
                'errorsCount' => 0,
                'processResult' => MessageProcessorInterface::ACK
            ], [
                'resultSuccess' => true,
                'readsCount' => 0,
                'errorsCount' => 0,
                'processResult' => MessageProcessorInterface::ACK
            ], [
                'resultSuccess' => false,
                'readsCount' => 0,
                'errorsCount' => 5,
                'processResult' => MessageProcessorInterface::REJECT
            ],
        ];
    }

    private function getJobProcessor(): JobProcessor
    {
        return self::getContainer()->get('oro_message_queue.job.processor');
    }
}
