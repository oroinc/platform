<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitAfterEvent;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

class EmbeddedFormSubmitAfterEventTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorRequires()
    {
        $expectedException = $this->getExpectedExceptionCode();
        $this->setExpectedException($expectedException);

        new EmbeddedFormSubmitAfterEvent(null);
    }

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

    /**
     * @return string
     */
    protected function getExpectedExceptionCode()
    {
        return version_compare(PHP_VERSION, '7.0.0', '>=') ? 'TypeError' : 'PHPUnit_Framework_Error';
    }
}
