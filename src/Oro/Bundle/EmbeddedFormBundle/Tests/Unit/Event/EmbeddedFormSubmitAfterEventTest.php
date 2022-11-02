<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitAfterEvent;
use Symfony\Component\Form\FormInterface;

class EmbeddedFormSubmitAfterEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetter()
    {
        $formEntity = new EmbeddedForm();
        $form = $this->createMock(FormInterface::class);
        $event = new EmbeddedFormSubmitAfterEvent([], $formEntity, $form);

        $this->assertSame($formEntity, $event->getFormEntity());
        $this->assertSame([], $event->getData());
        $this->assertSame($form, $event->getForm());
    }
}
