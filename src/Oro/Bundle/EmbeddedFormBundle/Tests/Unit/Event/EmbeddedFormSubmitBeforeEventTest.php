<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitBeforeEvent;
use PHPUnit\Framework\TestCase;

class EmbeddedFormSubmitBeforeEventTest extends TestCase
{
    public function testGetter(): void
    {
        $formEntity = new EmbeddedForm();
        $event = new EmbeddedFormSubmitBeforeEvent([], $formEntity);

        $this->assertSame($formEntity, $event->getFormEntity());
        $this->assertSame([], $event->getData());
    }
}
