<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor;
use Symfony\Component\Form\FormView;

class DependencyInjectionFormAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var string */
    protected $formServiceId;

    /** @var DependencyInjectionFormAccessor */
    protected $formAccessor;

    protected function setUp()
    {
        $this->container     = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->formServiceId = 'test_form';
        $this->formAccessor  = new DependencyInjectionFormAccessor($this->container, $this->formServiceId);
    }

    public function testGetForm()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->formServiceId)
            ->will($this->returnValue($form));

        $this->assertSame($form, $this->formAccessor->getForm());
    }

    public function testGetView()
    {
        // form
        //   field1
        //     field2
        $formView                       = new FormView();
        $field1View                     = new FormView($formView);
        $formView->children['field1']   = $field1View;
        $field2View                     = new FormView($field1View);
        $field1View->children['field2'] = $field2View;

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->formServiceId)
            ->will($this->returnValue($form));
        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));

        $this->assertSame($formView, $this->formAccessor->getView());
        $this->assertSame($field1View, $this->formAccessor->getView('field1'));
        $this->assertSame($field2View, $this->formAccessor->getView('field1.field2'));
    }

    public function testProcessedFields()
    {
        $this->assertNull($this->formAccessor->getProcessedFields());

        $processedFields = ['field' => 'block_id'];
        $this->formAccessor->setProcessedFields($processedFields);
        $this->assertSame($processedFields, $this->formAccessor->getProcessedFields());
    }
}
