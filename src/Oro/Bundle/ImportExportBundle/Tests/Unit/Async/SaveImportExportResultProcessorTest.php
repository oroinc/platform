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
    /** @var SaveImportExportResultProcessor */
    private $saveExportResultProcessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|JobRepository */
    private $jobRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportExportResultManager */
    private $importExportResultManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserManager */
    private $userManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->jobRepository = $this->createMock(JobRepository::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->importExportResultManager = $this->createMock(ImportExportResultManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(JobEntity::class)
            ->willReturn($this->jobRepository);

        $this->saveExportResultProcessor = new SaveImportExportResultProcessor(
            $this->importExportResultManager,
            $this->userManager,
            $doctrineHelper,
            $this->logger
        );
    }

    public function testSaveExportProcessor(): void
    {
        $this->assertInstanceOf(MessageProcessorInterface::class, $this->saveExportResultProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $this->saveExportResultProcessor);
    }

    public function testProcessWithValidMessage(): void
    {
        $this->logger
            ->expects($this->never())
            ->method('critical');
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = $this->createMock(MessageInterface::class);

        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'jobId' => '1',
                'type' => ProcessorRegistry::TYPE_EXPORT,
                'entity' => 'Acme',
                'options' => ['test1' => 'test2']
            ]));

        $job = new Job();
        $job->setId(1);

        $this->importExportResultManager
            ->expects($this->once())
            ->method('saveResult')
            ->with(1, ProcessorRegistry::TYPE_EXPORT, 'Acme', null, null);

        $this->jobRepository
            ->expects($this->once())
            ->method('findJobById')
            ->willReturn($job);

        $result = $this->saveExportResultProcessor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @param array $parameters
     * @param string $expectedError
     * @dataProvider getProcessWithInvalidMessageDataProvider
     */
    public function testProcessWithInvalidMessage(array $parameters, $expectedError): void
    {
        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($expectedError));
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = $this->createMock(MessageInterface::class);

        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($parameters));

        $job = new Job();
        $job->setId(1);

        $this->importExportResultManager
            ->expects($this->never())
            ->method('saveResult');

        $this->jobRepository
            ->expects($this->never())
            ->method('findJobById')
            ->willReturn($job);

        $result = $this->saveExportResultProcessor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @return array
     */
    public function getProcessWithInvalidMessageDataProvider()
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
