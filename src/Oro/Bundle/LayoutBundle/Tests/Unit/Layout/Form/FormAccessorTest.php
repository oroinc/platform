<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Symfony\Component\Form\FormView;

class FormAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var FormAccessor */
    protected $formAccessor;

    protected function setUp()
    {
        $this->form         = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->formAccessor = new FormAccessor($this->form);
    }

    public function testGetForm()
    {
        $this->assertSame($this->form, $this->formAccessor->getForm());
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

        $this->form->expects($this->once())
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
