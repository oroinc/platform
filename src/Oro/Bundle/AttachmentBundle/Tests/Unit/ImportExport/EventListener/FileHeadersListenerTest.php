<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileHeadersListener;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

class FileHeadersListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileHeadersListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new FileHeadersListener();
    }

    public function testAfterLoadEntityRulesAndBackendHeadersWhenNotFile(): void
    {
        $event = $this->createMock(LoadEntityRulesAndBackendHeadersEvent::class);
        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn('SampleEntity');
        $event->expects($this->never())
            ->method('addHeader');
        $event->expects($this->any())
            ->method('isFullData')
            ->willReturn(true);

        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);
    }

    public function testAfterLoadEntityRulesAndBackendHeadersWhenAlreadyExists(): void
    {
        $event = $this->createMock(LoadEntityRulesAndBackendHeadersEvent::class);
        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn(File::class);
        $event->expects($this->once())
            ->method('getHeaders')
            ->willReturn([['value' => 'uri']]);
        $event->expects($this->never())
            ->method('addHeader');
        $event->expects($this->once())
            ->method('setRule')
            ->with('UUID', ['value' => 'uuid', 'order' => 30]);
        $event->expects($this->any())
            ->method('isFullData')
            ->willReturn(true);

        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);
    }

    public function testAfterLoadEntityRulesAndBackendHeaders(): void
    {
        $event = $this->createMock(LoadEntityRulesAndBackendHeadersEvent::class);
        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn(File::class);
        $event->expects($this->once())
            ->method('getHeaders')
            ->willReturn([['value' => 'sampleHeader']]);
        $event->expects($this->once())
            ->method('addHeader')
            ->with(['value' => 'uri', 'order' => 20]);
        $event->expects($this->exactly(2))
            ->method('setRule')
            ->withConsecutive(
                ['URI', ['value' => 'uri', 'order' => 20]],
                ['UUID', ['value' => 'uuid', 'order' => 30]]
            );
        $event->expects($this->any())
            ->method('isFullData')
            ->willReturn(true);

        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);
    }
}
