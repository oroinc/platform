<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Event;

use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitBeforeEvent;

class EmbeddedFormSubmitBeforeEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testConstructorRequires()
    {
        new EmbeddedFormSubmitBeforeEvent(null);
    }

    public function testGetter()
    {
        $formEntity = $this->getMock('Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm');
        $event   = new EmbeddedFormSubmitBeforeEvent([], $formEntity);

        $this->assertSame($formEntity, $event->getFormEntity());
        $this->assertSame([], $event->getData());
    }
}
