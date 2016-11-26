<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormView;

use Oro\Bundle\UIBundle\Event\Events;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Twig\FormExtension;

class FormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormExtension
     */
    private $extension;

    /**
     * @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * Set up test environment
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new FormExtension($this->eventDispatcher);
    }

    public function testName()
    {
        $this->assertEquals('oro_form_process', $this->extension->getName());
    }

    public function testProcess()
    {
        $entity = new \stdClass();
        $formData = ['test'];

        $env = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::BEFORE_UPDATE_FORM_RENDER,
                $this->isInstanceOf(BeforeFormRenderEvent::class)
            )
            ->willReturnCallback(
                function ($eventName, BeforeFormRenderEvent $event) use ($formView, $formData, $entity) {
                    self::assertSame($formView, $event->getForm());
                    self::assertSame($formData, $event->getFormData());
                    self::assertSame($entity, $event->getEntity());
                }
            );

        $this->assertSame($formData, $this->extension->process($env, $formData, $formView, $entity));
    }

    public function testProcessWithoutEntity()
    {
        $formData = ['test'];

        $env = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::BEFORE_UPDATE_FORM_RENDER,
                $this->isInstanceOf(BeforeFormRenderEvent::class)
            )
            ->willReturnCallback(
                function ($eventName, BeforeFormRenderEvent $event) use ($formView, $formData) {
                    self::assertSame($formView, $event->getForm());
                    self::assertSame($formData, $event->getFormData());
                    self::assertNull($event->getEntity());
                }
            );

        $this->assertEquals($formData, $this->extension->process($env, $formData, $formView));
    }

    public function testGetFunctions()
    {
        $this->assertArrayHasKey('oro_form_process', $this->extension->getFunctions());
    }
}
