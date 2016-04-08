<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitBeforeEvent;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

class EmbeddedFormSubmitBeforeEventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorRequires()
    {
        $expectedException = $this->getExpectedExceptionCode();
        $this->setExpectedException($expectedException);
        
        new EmbeddedFormSubmitBeforeEvent(null);
    }

    public function testGetter()
    {
        $formEntity = new EmbeddedForm();
        $event   = new EmbeddedFormSubmitBeforeEvent([], $formEntity);

        $this->assertSame($formEntity, $event->getFormEntity());
        $this->assertSame([], $event->getData());
    }

    /**
     * @return string
     */
    protected function getExpectedExceptionCode()
    {
        return version_compare(PHP_VERSION, '7.0.0', '>=') ? 'TypeError' : 'PHPUnit_Framework_Error';
    }
}
