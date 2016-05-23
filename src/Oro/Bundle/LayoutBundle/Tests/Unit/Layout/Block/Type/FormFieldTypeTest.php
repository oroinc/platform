<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\Form\FormView;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Extension\PreloadedExtension;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\FormFieldType;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\VisibleExtension;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class FormFieldTypeTest extends BlockTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [],
                ['block' => [new VisibleExtension()]]
            )
        ];
    }

    public function testResolveOptionsWithoutFormName()
    {
        $options = $this->resolveOptions(FormFieldType::NAME, ['field_path' => 'firstName']);
        $this->assertEquals('form', $options['form_name']);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "field_path" is missing.
     */
    public function testResolveOptionsWithoutFieldName()
    {
        $this->resolveOptions(FormFieldType::NAME, []);
    }

    public function testBuildView()
    {
        $formName = 'test_form';
        $formPath = 'firstName';
        $formView = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse($formView->isRendered());
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test_form.
     */
    public function testBuildViewWithoutForm()
    {
        $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => 'test_form', 'field_path' => 'firstName']
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "context[test_form]" argument type. Expected "Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testBuildViewWithInvalidForm()
    {
        $formName = 'test_form';

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, 123);
        $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => 'firstName']
        );
    }

    public function testFinishViewForVisibleField()
    {
        $formName = 'test_form';
        $formPath = 'firstName';
        $formView = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath, 'visible' => true]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse($formView->isRendered());
    }

    public function testFinishViewForInvisibleField()
    {
        $formName = 'test_form';
        $formPath = 'firstName';
        $formView = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath, 'visible' => false]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertTrue($formView->isRendered());
    }

    public function testFinishViewForFieldWithVisibleOptionAsNotEvaluatedExpression()
    {
        $formName = 'test_form';
        $formPath = 'firstName';
        $formView = new FormView();

        $formAccessor = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setOptional([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            FormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath, 'visible' => new Condition\FalseCondition()]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse($formView->isRendered());
    }

    public function testGetName()
    {
        $type = $this->getBlockType(FormFieldType::NAME);

        $this->assertSame(FormFieldType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(FormFieldType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
