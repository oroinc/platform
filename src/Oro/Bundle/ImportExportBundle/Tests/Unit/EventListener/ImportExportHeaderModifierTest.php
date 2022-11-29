<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\EventListener;

use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\EventListener\ImportExportHeaderModifier;
use PHPUnit\Framework\TestCase;

class ImportExportHeaderModifierTest extends TestCase
{
    public function testAddHeader()
    {
        $event  = new LoadEntityRulesAndBackendHeadersEvent(
            'entityName',
            [['value' => 'header1', 'order' => 20]],
            ['rule1' => ['value' => 'header1', 'order' => 20]],
            'delim',
            'conv-type'
        );

        ImportExportHeaderModifier::addHeader(
            $event,
            'header1',
            'rule1',
            100
        );

        ImportExportHeaderModifier::addHeader(
            $event,
            'header2',
            'rule2',
            100
        );

        $this->assertEquals(
            [
                ['value' => 'header1', 'order' => 20],
                ['value' => 'header2', 'order' => 100]
            ],
            $event->getHeaders()
        );
    }
}
