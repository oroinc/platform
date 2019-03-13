<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\ImportExportBundle\Async\SaveImportExportResultProcessor;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SaveImportExportResultProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var SaveImportExportResultProcessor */
    private $saveExportResultProcessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|JobStorage */
    private $jobStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ImportExportResultManager */
    private $importExportResultManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserManager */
    private $userManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    protected function setUp()
    {
        $this->jobStorage = self::createMock(JobStorage::class);
        $this->userManager = self::createMock(UserManager::class);
        $this->importExportResultManager = self::createMock(ImportExportResultManager::class);
        $this->logger = self::createMock(LoggerInterface::class);

        $this->saveExportResultProcessor = new SaveImportExportResultProcessor(
            $this->importExportResultManager,
            $this->userManager,
            $this->jobStorage,
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
            ->expects(self::never())
            ->method('critical');
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = self::createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = self::createMock(MessageInterface::class);

        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'jobId' => '1',
                'type' => ProcessorRegistry::TYPE_EXPORT,
                'entity' => 'Acme'
            ]));

        $job = new Job();
        $job->setId(1);

        $this->importExportResultManager
            ->expects(self::once())
            ->method('saveResult')
            ->with(1, ProcessorRegistry::TYPE_EXPORT, 'Acme', null, null);

        $this->jobStorage
            ->expects(self::once())
            ->method('findJobById')
            ->willReturn($job);

        $result = $this->saveExportResultProcessor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithInvalidMessage(): void
    {
        $this->logger
            ->expects(self::once())
            ->method('critical')
            ->with(self::stringContains('Not enough required parameters:'));
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = self::createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = self::createMock(MessageInterface::class);

        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'jobId' => 1,
                'type' => 'invalid_processor_type',
                'entity' => null
            ]));

        $job = new Job();
        $job->setId(1);

        $this->importExportResultManager
            ->expects(self::never())
            ->method('saveResult');

        $this->jobStorage
            ->expects(self::never())
            ->method('findJobById')
            ->willReturn($job);

        $result = $this->saveExportResultProcessor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
