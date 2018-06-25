<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

class LoadEntityRulesAndBackendHeadersEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $event = new LoadEntityRulesAndBackendHeadersEvent(
            'entityName',
            ['header1'],
            ['rule1' => ['rval1']],
            'delim',
            'conv-type'
        );

        $this->assertEquals('entityName', $event->getEntityName());
        $this->assertEquals(['header1'], $event->getHeaders());
        $this->assertEquals(['rule1' => ['rval1']], $event->getRules());
        $this->assertEquals('delim', $event->getConvertDelimiter());
        $this->assertEquals('conv-type', $event->getConversionType());

        $event->addHeader('header2');
        $event->setRule('rule1', ['updated-rule']);
        $this->assertEquals('entityName', $event->getEntityName());
        $this->assertEquals(['header1', 'header2'], $event->getHeaders());
        $this->assertEquals(['rule1' => ['updated-rule']], $event->getRules());
        $this->assertEquals('delim', $event->getConvertDelimiter());
        $this->assertEquals('conv-type', $event->getConversionType());
    }
}
