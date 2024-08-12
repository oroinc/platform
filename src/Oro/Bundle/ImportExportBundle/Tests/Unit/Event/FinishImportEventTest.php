<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\FinishImportEvent;
use PHPUnit\Framework\TestCase;

class FinishImportEventTest extends TestCase
{
    public function testCreateEvent(): void
    {
        $event = new FinishImportEvent(1, 'alias', 'type', ['parameter' => 'value']);

        self::assertEquals(1, $event->getJobId());
        self::assertEquals('alias', $event->getProcessorAlias());
        self::assertEquals('type', $event->getType());
        self::assertEquals(['parameter' => 'value'], $event->getOptions());
    }
}
