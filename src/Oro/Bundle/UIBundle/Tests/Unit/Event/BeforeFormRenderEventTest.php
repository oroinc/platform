<?php

namespace Oro\Bundle\UI\Tests\Unit\Event;

use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;

class BeforeFormRenderEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $env = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $formView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();
        $formData = array('test');
        $entity = new \stdClass();

        $event = new BeforeFormRenderEvent($formView, $formData, $env, $entity);

        $this->assertEquals($formView, $event->getForm());
        $this->assertEquals($formData, $event->getFormData());
        $this->assertEquals($env, $event->getTwigEnvironment());
        $formDataNew = array('test_new');
        $event->setFormData($formDataNew);
        $this->assertEquals($formDataNew, $event->getFormData());
        $this->assertEquals($entity, $event->getEntity());
    }
}
