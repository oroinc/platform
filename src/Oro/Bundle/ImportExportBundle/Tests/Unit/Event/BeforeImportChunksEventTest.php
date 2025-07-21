<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use PHPUnit\Framework\TestCase;

class BeforeImportChunksEventTest extends TestCase
{
    public function testEvent(): void
    {
        $body = ['body'];

        $event = new BeforeImportChunksEvent($body);
        $this->assertSame($body, $event->getBody());
    }
}
