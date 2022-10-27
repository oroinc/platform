<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\SaveImportExportResultProcessor;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SaveImportExportResultProcessorTest extends \PHPUnit\Framework\TestCase
{
    private JobRepository|\PHPUnit\Framework\MockObject\MockObject $jobRepository;

    private ImportExportResultManager|\PHPUnit\Framework\MockObject\MockObject $importExportResultManager;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private SaveImportExportResultProcessor $saveExportResultProcessor;

    protected function setUp(): void
    {
        $this->jobRepository = $this->createMock(JobRepository::class);
        $this->importExportResultManager = $this->createMock(ImportExportResultManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->with(JobEntity::class)
            ->willReturn($this->jobRepository);

        $this->saveExportResultProcessor = new SaveImportExportResultProcessor(
            $this->importExportResultManager,
            $doctrineHelper,
            $this->logger
        );
    }

    public function testSaveExportProcessor(): void
    {
        self::assertInstanceOf(MessageProcessorInterface::class, $this->saveExportResultProcessor);
        self::assertInstanceOf(TopicSubscriberInterface::class, $this->saveExportResultProcessor);
    }

    public function testProcessWithNotFoundJob(): void
    {
        $this->jobRepository->expects(self::once())
            ->method('findJobById')
            ->with(1)
            ->willReturn(null);

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Job not found');

        $this->importExportResultManager->expects(self::never())
            ->method('saveResult');

        $message = new Message();
        $message->setBody([
            'jobId' => 1,
            'entity' => \stdClass::class,
            'type' => 'type',
        ]);

        $result = $this->saveExportResultProcessor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcess(): void
    {
        $jobId = 1;

        $job = new Job();
        $job->setId($jobId);

        $this->jobRepository->expects(self::once())
            ->method('findJobById')
            ->with($jobId)
            ->willReturn($job);

        $type = 'type';
        $entity = \stdClass::class;
        $userId = 1;
        $owner = new UserStub(1);
        $options = [];

        $this->importExportResultManager->expects(self::once())
            ->method('saveResult')
            ->with($jobId, $type, $entity, $owner, null, $options)
            ->willReturn(new ImportExportResult());

        $message = new Message();
        $message->setBody([
            'jobId' => $jobId,
            'entity' => $entity,
            'type' => $type,
            'userId' => $userId,
            'owner' => $owner,
            'options' => $options,
        ]);

        $result = $this->saveExportResultProcessor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
