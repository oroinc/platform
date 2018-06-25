<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::EXPORT], ExportMessageProcessor::getSubscribedTopics());
    }

    public function invalidMessageProvider()
    {
        return [
            [
                'Got invalid message',
                ['jobName' => 'name', 'processorAlias' => 'alias'],
            ],
            [
                'Got invalid message',
                ['jobId' => 1, 'processorAlias' => 'alias'],
            ],
            [
                'Got invalid message',
                ['jobId' => 1, 'jobName' => 'name'],
            ],
        ];
    }

    /**
     * @dataProvider invalidMessageProvider
     *
     * @param string $loggerMessage
     * @param array $messageBody
     */
    public function testShouldRejectMessageAndLogCriticalIfInvalidMessage($loggerMessage, $messageBody)
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->equalTo($loggerMessage))
        ;

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $processor = new ExportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createJobStorageMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldSetOrganizationAndDoExport()
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

        $job = new Job();

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with($this->equalTo(1))
            ->will($this->returnCallback(function ($jobId, $callback) use ($jobRunner, $job) {
                return $callback($jobRunner, $job);
            }))
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0'))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
        ;

        $organizationRepository = $this->createOrganizationRepositoryMock();
        $organizationRepository
            ->expects($this->once())
            ->method('find')
        ;

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->equalTo(Organization::class))
            ->willReturn($organizationRepository)
        ;

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler
            ->expects($this->once())
            ->method('getExportResult')
            ->willReturn($exportResult)
        ;

        $processor = new ExportMessageProcessor(
            $jobRunner,
            $jobStorage,
            $logger
        );
        $processor->setDoctrineHelper($doctrineHelper);
        $processor->setExportHandler($exportHandler);


        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobId' => 1,
            'jobName' => 'name',
            'processorAlias' => 'alias',
            'organizationId' => 2,
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::ACK, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private function createDoctrineHelperMock()
    {
        return $this->createMock(DoctrineHelper::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|OrganizationRepository
     */
    private function createOrganizationRepositoryMock()
    {
        return $this->createMock(OrganizationRepository::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->createMock(ExportHandler::class);
    }
}
