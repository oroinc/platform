<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\SaveImportExportResultProcessor;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
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
        $userManager = $this->createMock(UserManager::class);
        $this->importExportResultManager = $this->createMock(ImportExportResultManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->with(JobEntity::class)
            ->willReturn($this->jobRepository);

        $this->saveExportResultProcessor = new SaveImportExportResultProcessor(
            $this->importExportResultManager,
            $userManager,
            $doctrineHelper,
            $this->logger
        );
    }

    public function testSaveExportProcessor(): void
    {
        self::assertInstanceOf(MessageProcessorInterface::class, $this->saveExportResultProcessor);
        self::assertInstanceOf(TopicSubscriberInterface::class, $this->saveExportResultProcessor);
    }

    public function testProcessWithValidMessage(): void
    {
        $this->logger->expects(self::never())
            ->method('critical');
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'jobId' => '1',
                'type' => ProcessorRegistry::TYPE_EXPORT,
                'entity' => 'Acme',
                'options' => ['test1' => 'test2']
            ]));

        $job = new Job();
        $job->setId(1);

        $this->importExportResultManager->expects(self::once())
            ->method('saveResult')
            ->with(1, ProcessorRegistry::TYPE_EXPORT, 'Acme', null, null);

        $this->jobRepository->expects(self::once())
            ->method('findJobById')
            ->willReturn($job);

        $result = $this->saveExportResultProcessor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @dataProvider getProcessWithInvalidMessageDataProvider
     */
    public function testProcessWithInvalidMessage(array $parameters, string $expectedError): void
    {
        $this->logger->expects(self::once())
            ->method('critical')
            ->with(self::stringContains($expectedError));
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($parameters));

        $job = new Job();
        $job->setId(1);

        $this->importExportResultManager->expects(self::never())
            ->method('saveResult');

        $this->jobRepository->expects(self::never())
            ->method('findJobById')
            ->willReturn($job);

        $result = $this->saveExportResultProcessor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function getProcessWithInvalidMessageDataProvider(): array
    {
        return [
            'without jobId' => [
                'parameters' => [
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'entity' => '1',
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'Error occurred during save result: The required option "jobId" is missing.'
            ],
            'without entity' => [
                'parameters' => [
                    'jobId' => 1,
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'Error occurred during save result: The required option "entity" is missing.'
            ],
            'without type' => [
                'parameters' => [
                    'jobId' => 1,
                    'entity' => '1',
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'Error occurred during save result: The required option "type" is missing.'
            ],
            'invalid processor' => [
                'parameters' => [
                    'jobId' => 1,
                    'type' => 'invalid_type',
                    'entity' => '1',
                    'options' => ['test1' => 'test2']
                ],
                'expectedError' => 'The option "type" with value "invalid_type" is invalid. Accepted values are:'
            ],
            'options not array' => [
                'parameters' => [
                    'jobId' => 1,
                    'type' => ProcessorRegistry::TYPE_EXPORT,
                    'entity' => '1',
                    'options' => 1
                ],
                'expectedError' => 'is expected to be of type "array", but is of type'
            ]
        ];
    }
}
