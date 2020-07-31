<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\EventListener\FileHeadersListener;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

class FileHeadersListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileHeadersListener */
    private $listener;

    /** @var LoadEntityRulesAndBackendHeadersEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    protected function setUp(): void
    {
        $this->listener = new FileHeadersListener();
        $this->event = $this->createMock(LoadEntityRulesAndBackendHeadersEvent::class);
    }

    public function testAfterLoadEntityRulesAndBackendHeadersWhenNotFile(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn('SampleEntity');

        $this->event
            ->expects($this->never())
            ->method('addHeader');

        $this->listener->afterLoadEntityRulesAndBackendHeaders($this->event);
    }

    public function testAfterLoadEntityRulesAndBackendHeadersWhenAlreadyExists(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn(File::class);

        $this->event
            ->expects($this->once())
            ->method('getHeaders')
            ->willReturn([['value' => 'uri']]);

        $this->event
            ->expects($this->never())
            ->method('addHeader');

        $this->event
            ->expects($this->once())
            ->method('setRule')
            ->with('UUID', ['value' => 'uuid', 'order' => 30]);

        $this->listener->afterLoadEntityRulesAndBackendHeaders($this->event);
    }

    public function testAfterLoadEntityRulesAndBackendHeaders(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn(File::class);

        $this->event
            ->expects($this->once())
            ->method('getHeaders')
            ->willReturn([['value' => 'sampleHeader']]);

        $this->event
            ->expects($this->once())
            ->method('addHeader')
            ->with(['value' => 'uri', 'order' => 20]);

        $this->event
            ->expects($this->exactly(2))
            ->method('setRule')
            ->withConsecutive(
                ['URI', ['value' => 'uri', 'order' => 20]],
                ['UUID', ['value' => 'uuid', 'order' => 30]]
            );

        $this->listener->afterLoadEntityRulesAndBackendHeaders($this->event);
    }
}
