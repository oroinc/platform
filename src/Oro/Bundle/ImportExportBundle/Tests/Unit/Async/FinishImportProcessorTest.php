<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Oro\Bundle\ImportExportBundle\Async\FinishImportProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topic\FinishImportTopic;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\FinishImportEvent;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FinishImportProcessorTest extends TestCase
{
    private EventDispatcher&MockObject $eventDispatcher;
    private FinishImportProcessor $finishImportProcessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->finishImportProcessor = new FinishImportProcessor($this->eventDispatcher);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals([FinishImportTopic::getName()], FinishImportProcessor::getSubscribedTopics());
    }

    public function testProcess(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $message = new Message();
        $message->setBody([
            'rootImportJobId' => 123,
            'processorAlias' => 'processor_alias',
            'type' => ProcessorRegistry::TYPE_IMPORT,
            'options' => []
        ]);

        $event = new FinishImportEvent(123, 'processor_alias', ProcessorRegistry::TYPE_IMPORT, []);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, Events::FINISH_IMPORT);

        $result = $this->finishImportProcessor->process($message, $session);
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
