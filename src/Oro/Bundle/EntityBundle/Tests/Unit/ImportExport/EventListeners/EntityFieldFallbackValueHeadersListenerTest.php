<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ImportExport\EventListeners;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ImportExport\EventListeners\EntityFieldFallbackValueHeadersListener;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

class EntityFieldFallbackValueHeadersListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityFieldFallbackValueHeadersListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->listener = new EntityFieldFallbackValueHeadersListener();
    }

    public function testAfterLoadEntityRulesAndBackendHeaders()
    {
        $event = new LoadEntityRulesAndBackendHeadersEvent(EntityFieldFallbackValue::class, [], [], ':', 'full', true);
        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);
        $this->assertSame([['value' => 'value', 'order' => 10005]], $event->getHeaders());
        $this->assertSame(['value' => ['value' => 'value', 'order' => 10005]], $event->getRules());
    }

    public function testAfterLoadEntityRulesAndBackendHeadersDuplicateHeader()
    {
        $event = new LoadEntityRulesAndBackendHeadersEvent(
            EntityFieldFallbackValue::class,
            [['headerName' => 'headerTitle'], ['value' => 'value']],
            [['someRule' => ['headerName' => 'headerTitle']], ['value' => ['value' => 'value']]],
            ':',
            'full',
            true
        );
        $this->listener->afterLoadEntityRulesAndBackendHeaders($event);
        $this->assertSame([
            ['headerName' => 'headerTitle'],
            ['value' => 'value'],
            ['value' => 'value', 'order' => 10005]
        ], $event->getHeaders());
        $this->assertSame(
            [
                ['someRule' => ['headerName' => 'headerTitle']],
                ['value' => ['value' => 'value']],
                'value' => ['value' => 'value', 'order' => 10005]
            ],
            $event->getRules()
        );
    }
}
