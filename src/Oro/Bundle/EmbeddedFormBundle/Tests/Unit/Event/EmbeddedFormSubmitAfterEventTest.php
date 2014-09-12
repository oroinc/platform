<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitAfterEvent;

class EmbeddedFormSubmitAfterEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testConstructorRequires()
    {
        new EmbeddedFormSubmitAfterEvent(null);
    }

    public function testGetter()
    {
        $formEntity = $this->getMock('Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm');
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
