<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class FormAccessorTest extends TestCase
{
    private const FORM_NAME = 'test_form';

    private FormInterface&MockObject $form;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects($this->any())
            ->method('getName')
            ->willReturn(self::FORM_NAME);
    }

    public function testGetForm(): void
    {
        $formAccessor = new FormAccessor($this->form);
        $this->assertSame($this->form, $formAccessor->getForm());
    }

    public function testToString(): void
    {
        $formAccessor = new FormAccessor($this->form);
        $this->assertEquals('name:'.self::FORM_NAME, $formAccessor->toString());
    }

    public function testGetHash(): void
    {
        $formAccessor = new FormAccessor(
            $this->form,
            FormAction::createByRoute('test_route', ['foo' => 'bar']),
            'post',
            'multipart/form-data'
        );
        $this->assertEquals(
            self::FORM_NAME . ';action_route:test_route;method:post;enctype:multipart/form-data',
            $formAccessor->getHash()
        );
    }

    public function testParamsInitializer(): void
    {
        $formAccessor = new FormAccessor($this->form);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formView = new FormView();

        $formView->vars['multipart'] = false;

        $this->form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $this->form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getAction')
            ->willReturn($formAction);
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->willReturn($formMethod);

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertNull($formAccessor->getEnctype());
        $this->assertEquals('name:'.self::FORM_NAME, $formAccessor->toString());
    }

    public function testParamsInitializerForMultipartForm(): void
    {
        $formAccessor = new FormAccessor($this->form);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formView = new FormView();

        $formView->vars['multipart'] = true;

        $this->form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $this->form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getAction')
            ->willReturn($formAction);
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->willReturn($formMethod);

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertEquals('multipart/form-data', $formAccessor->getEnctype());
        $this->assertEquals('name:'.self::FORM_NAME, $formAccessor->toString());
    }

    public function testGetView(): void
    {
        // form
        //   field1
        //     field2
        $formView = new FormView();
        $field1View = new FormView($formView);
        $formView->children['field1'] = $field1View;
        $field2View = new FormView($field1View);
        $field1View->children['field2'] = $field2View;

        $this->form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $formAccessor = new FormAccessor($this->form);
        $this->assertSame($formView, $formAccessor->getView());
        $this->assertSame($field1View, $formAccessor->getView('field1'));
        $this->assertSame($field2View, $formAccessor->getView('field1.field2'));
    }

    public function testProcessedFields(): void
    {
        $formAccessor = new FormAccessor($this->form);

        $this->assertNull($formAccessor->getProcessedFields());

        $processedFields = ['field' => 'block_id'];
        $formAccessor->setProcessedFields($processedFields);
        $this->assertSame($processedFields, $formAccessor->getProcessedFields());
    }
}
