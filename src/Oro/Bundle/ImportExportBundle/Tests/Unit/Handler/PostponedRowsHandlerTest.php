<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Handler;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\PostponedRowsHandler;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\ExtensionInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner;
use Symfony\Component\Translation\TranslatorInterface;

class PostponedRowsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PostponedRowsHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $handler;

    /** @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $messageProducer;

    /** @var Job|\PHPUnit_Framework_MockObject_MockObject */
    private $currentJob;

    /** @var JobRunner|\PHPUnit_Framework_MockObject_MockObject  */
    private $jobRunner;

    /** @var JobProcessor|\PHPUnit_Framework_MockObject_MockObject */
    private $jobProcessor;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    public function setUp()
    {
        $fileManagerMock = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writerChain = $this->getMockBuilder(WriterChain::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->getMockBuilder(MessageProducerInterface::class)
            ->getMock();
        $this->currentJob = $this->getMockBuilder(Job::class)
            ->getMock();
        $rootJob = $this->getMockBuilder(Job::class)
            ->getMock();
        $this->jobProcessor = $this->getMockBuilder(JobProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = new PostponedRowsHandler(
            $fileManagerMock,
            $this->messageProducer,
            $writerChain,
            $this->translator
        );

        $rootJob->method('getName')
            ->willReturn('name');
        $this->currentJob->method('getRootJob')
            ->willReturn($rootJob);
        $this->currentJob->method('getId')
            ->willReturn(1);
        $jobExtension = $this->createMock(ExtensionInterface::class);
        $this->jobRunner = new JobRunner($this->jobProcessor, $jobExtension, $this->currentJob);
    }

    public function testItCreatesIncrementedJob()
    {
        $this->jobProcessor
            ->method('findOrCreateChildJob')
            ->willReturn($this->currentJob);

        $expectedMessage = new Message();
        $expectedMessage->setBody(['jobId' => 1, 'attempts' => 1, 'fileName' => '']);
        $expectedMessage->setDelay(PostponedRowsHandler::DELAY_SECONDS);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(
                Topics::HTTP_IMPORT,
                $expectedMessage
            );

        $result = [];
        $body = ['attempts' => 0];

        $this->handler->postpone($this->jobRunner, $this->currentJob, '', $body, $result);
    }

    public function testItStopsAfterFifthAttempt()
    {
        $this->jobProcessor
            ->method('findOrCreateChildJob')
            ->willReturn($this->currentJob);

        $result = [];
        $body = ['attempts' => PostponedRowsHandler::MAX_ATTEMPTS];
        $this->messageProducer->expects($this->never())->method('send');
        $this->handler->postpone($this->jobRunner, $this->currentJob, '', $body, $result);
    }

    public function testItAddsErrorMessageWhenPostponeRowsPresent()
    {
        $this->jobProcessor
            ->method('findOrCreateChildJob')
            ->willReturn($this->currentJob);

        $body = ['attempts' => PostponedRowsHandler::MAX_ATTEMPTS];
        $result = [];
        $result['postponedRows'] = ['elem1', 'elem2'];

        $result['counts']['errors'] = 0;
        $this->messageProducer->expects($this->never())->method('send');
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'oro.importexport.import.postponed_rows',
                ['%postponedRows%' => 2]
            );
        $this->handler->postpone($this->jobRunner, $this->currentJob, '', $body, $result);
    }
}
