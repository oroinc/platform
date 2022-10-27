<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\PreloadEntityEvent;

class PreloadEntityEventTest extends \PHPUnit\Framework\TestCase
{
    public function testAccessors(): void
    {
        $entities = [new \stdClass(), new \stdClass()];
        $fieldsToPreload = ['sampleField1' => [], 'sampleField2' => []];
        $context = ['sample_key' => 'sample_value'];
        $event = new PreloadEntityEvent($entities, $fieldsToPreload, $context);

        $this->assertSame($entities, $event->getEntities());
        $this->assertSame(array_keys($fieldsToPreload), $event->getFieldsToPreload());
        $this->assertSame($context, $event->getContext());
    }

    public function testHasSubFields(): void
    {
        $entities = [new \stdClass()];
        $fieldsToPreload = ['sampleField1' => ['sampleField1_1'], 'sampleField2' => []];
        $context = [];
        $event = new PreloadEntityEvent($entities, $fieldsToPreload, $context);

        $this->assertTrue($event->hasSubFields('sampleField1'));
        $this->assertFalse($event->hasSubFields('sampleField2'));
    }
}
