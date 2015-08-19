<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Form;

use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

class FormAccessorTest extends \PHPUnit_Framework_TestCase
{
    const FORM_NAME = 'test_form';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->form->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(self::FORM_NAME));
    }

    public function testGetForm()
    {
        $formAccessor = new FormAccessor($this->form);
        $this->assertSame($this->form, $formAccessor->getForm());
    }

    public function testToString()
    {
        $formAccessor = new FormAccessor($this->form);
        $this->assertEquals(self::FORM_NAME, $formAccessor->toString());
    }

    public function testToStringWithAllParams()
    {
        $formAccessor = new FormAccessor(
            $this->form,
            FormAction::createByRoute('test_route', ['foo' => 'bar']),
            'post',
            'multipart/form-data'
        );
        $this->assertEquals(
            self::FORM_NAME . ';action_route:test_route;method:post;enctype:multipart/form-data',
            $formAccessor->toString()
        );
    }

    public function testParamsInitializer()
    {
        $formAccessor = new FormAccessor($this->form);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $form       = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formView   = new FormView();

        $formView->vars['multipart'] = false;

        $this->form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));
        $this->form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $formConfig->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue($formAction));
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($formMethod));

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertNull($formAccessor->getEnctype());
        $this->assertEquals(self::FORM_NAME, $formAccessor->toString());
    }

    public function testParamsInitializerForMultipartForm()
    {
        $formAccessor = new FormAccessor($this->form);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formView   = new FormView();

        $formView->vars['multipart'] = true;

        $this->form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));
        $this->form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $formConfig->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue($formAction));
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($formMethod));

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertEquals('multipart/form-data', $formAccessor->getEnctype());
        $this->assertEquals(self::FORM_NAME, $formAccessor->toString());
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

        $formAccessor = new FormAccessor($this->form);
        $this->assertSame($formView, $formAccessor->getView());
        $this->assertSame($field1View, $formAccessor->getView('field1'));
        $this->assertSame($field2View, $formAccessor->getView('field1.field2'));
    }

    public function testProcessedFields()
    {
        $formAccessor = new FormAccessor($this->form);

        $this->assertNull($formAccessor->getProcessedFields());

        $processedFields = ['field' => 'block_id'];
        $formAccessor->setProcessedFields($processedFields);
        $this->assertSame($processedFields, $formAccessor->getProcessedFields());
    }
}
