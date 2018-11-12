<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class ExportMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.export');

        $this->assertInstanceOf(ExportMessageProcessor::class, $instance);
    }

    /**
     * @dataProvider exportProcessDataProvider
     */
    public function testShouldProcessExport(
        $resultSuccess,
        $resultReadsCount,
        $resultErrorsCount,
        $expectedResult
    ) {
        /** @var User $user */
        $user = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepository(User::class)->find(1);

        $rootJob = $this->getJobProcessor()->findOrCreateRootJob(
            'test_import_message',
            'oro:import:http:oro_test.add_or_replace:test_import_message'
        );
        $childJob = $this->getJobProcessor()->findOrCreateChildJob(
            'oro:import:http:oro_test.add_or_replace:test_import_message:chunk.1',
            $rootJob
        );

        $message = new NullMessage();
        $message->setMessageId('abc');
        $message->setBody(json_encode([
            'jobId' => $childJob->getId(),
            'jobName' => 'job_name',
            'processorAlias' => 'alias',
        ]));


        $exportResult = [
            'success' => $resultSuccess,
            'url' => 'http://localhost',
            'readsCount' => $resultReadsCount,
            'errorsCount' => $resultErrorsCount,
            'entities' => 'User',
        ];

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler
            ->expects($this->once())
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

        $this->getContainer()->set('oro_importexport.handler.export', $exportHandler);

        $processor = $this->getContainer()->get('oro_importexport.async.export');

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals($expectedResult, $result);
        $this->assertCount(5, $childJob->getData());
        $this->assertEquals($exportResult, $childJob->getData());
    }

    public function exportProcessDataProvider()
    {
        return [
            [
                'resultSuccess' => true,
                'readsCount' => 100,
                'errorsCount' => 0,
                'processResult' => ExportMessageProcessor::ACK
            ], [
                'resultSuccess' => true,
                'readsCount' => 0,
                'errorsCount' => 0,
                'processResult' => ExportMessageProcessor::ACK
            ], [
                'resultSuccess' => false,
                'readsCount' => 0,
                'errorsCount' => 5,
                'processResult' => ExportMessageProcessor::REJECT
            ],
        ];
    }

    /**
     * @return object
     */
    private function getConfigManager()
    {
        return $this->getContainer()->get('oro_config.user');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->createMock(ExportHandler::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \Oro\Component\MessageQueue\Job\JobProcessor
     */
    private function getJobProcessor()
    {
        return $this->getContainer()->get('oro_message_queue.job.processor');
    }
}
