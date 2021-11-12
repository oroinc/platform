<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::EXPORT], ExportMessageProcessor::getSubscribedTopics());
    }

    public function invalidMessageProvider(): array
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
     */
    public function testShouldRejectMessageAndLogCriticalIfInvalidMessage(string $loggerMessage, array $messageBody)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with($this->equalTo($loggerMessage));

        $message = new Message();
        $message->setBody(JSON::encode($messageBody));

        $processor = new ExportMessageProcessor(
            $this->createMock(JobRunner::class),
            $this->createMock(FileManager::class),
            $logger
        );

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSetOrganizationAndDoExport()
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

        $job = new Job();

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with($this->equalTo(1))
            ->willReturnCallback(function ($jobId, $callback) use ($jobRunner, $job) {
                return $callback($jobRunner, $job);
            });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0'));

        $organizationRepository = $this->createMock(OrganizationRepository::class);
        $organizationRepository->expects($this->once())
            ->method('find');

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->equalTo(Organization::class))
            ->willReturn($organizationRepository);

        $exportHandler = $this->createMock(ExportHandler::class);
        $exportHandler->expects($this->once())
            ->method('getExportResult')
            ->willReturn($exportResult);

        $processor = new ExportMessageProcessor(
            $jobRunner,
            $this->createMock(FileManager::class),
            $logger
        );
        $processor->setDoctrineHelper($doctrineHelper);
        $processor->setExportHandler($exportHandler);

        $message = new Message();
        $message->setBody(JSON::encode([
            'jobId' => 1,
            'jobName' => 'name',
            'processorAlias' => 'alias',
            'organizationId' => 2,
        ]));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
