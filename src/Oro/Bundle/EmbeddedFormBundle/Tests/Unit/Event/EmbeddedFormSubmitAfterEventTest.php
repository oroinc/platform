<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitAfterEvent;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

class EmbeddedFormSubmitAfterEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetter()
    {
        $formEntity = new EmbeddedForm();
        $form = $this
            ->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $event   = new EmbeddedFormSubmitAfterEvent([], $formEntity, $form);

        $this->assertSame($formEntity, $event->getFormEntity());
        $this->assertSame([], $event->getData());
        $this->assertSame($form, $event->getForm());
    }
}
