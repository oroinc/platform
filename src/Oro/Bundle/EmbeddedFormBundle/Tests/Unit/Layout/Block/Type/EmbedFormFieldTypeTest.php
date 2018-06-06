<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormFieldType;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ConfigurableTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Extension\PreloadedExtension;
use Symfony\Component\Form\FormView;

class EmbedFormFieldTypeTest extends BlockTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $visibleExtension = new ConfigurableTypeExtension();
        $visibleExtension->setOptionsConfig([
            'visible' => [
                'default' => true,
            ]
        ]);
        return [
            new PreloadedExtension(
                [],
                ['block' => [$visibleExtension]]
            )
        ];
    }

    public function testResolveOptionsWithoutFormName()
    {
        $options = $this->resolveOptions(EmbedFormFieldType::NAME, ['field_path' => 'firstName']);
        $this->assertEquals('form', $options['form_name']);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "field_path" is missing.
     */
    public function testResolveOptionsWithoutFieldName()
    {
        $this->resolveOptions(EmbedFormFieldType::NAME, []);
    }

    public function testGetBlockView()
    {
        $formName = 'test_form';
        $formPath = 'firstName';
        $formView = new FormView();

        $formAccessor = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse($formView->isRendered());
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test_form.
     */
    public function testGetBlockViewWithoutForm()
    {
        $this->getBlockView(
            EmbedFormFieldType::NAME,
            ['form_name' => 'test_form', 'field_path' => 'firstName']
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "context[test_form]" argument type. Expected "Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testBuildViewWithInvalidForm()
    {
        $formName = 'test_form';

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, 123);
        $this->getBlockView(
            EmbedFormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => 'firstName']
        );
    }

    public function testGetBlockViewForVisibleField()
    {
        $formName = 'test_form';
        $formPath = 'firstName';
        $formView = new FormView();

        $formAccessor = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath, 'visible' => true]
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse($formView->isRendered());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     */
    public function testGetBlockViewForInvisibleField()
    {
        $formName = 'test_form';
        $formPath = 'firstName';

        $this->context->getResolver()->setDefined([$formName]);
        $this->getBlockView(
            EmbedFormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath, 'visible' => false]
        );
    }

    public function testGetBlockViewForFieldWithVisibleOptionAsNotEvaluatedExpression()
    {
        $formName = 'test_form';
        $formPath = 'firstName';
        $formView = new FormView();

        $formAccessor = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface');
        $formAccessor->expects($this->once())
            ->method('getView')
            ->with($formPath)
            ->will($this->returnValue($formView));

        $this->context->getResolver()->setDefined([$formName]);
        $this->context->set($formName, $formAccessor);
        $view = $this->getBlockView(
            EmbedFormFieldType::NAME,
            ['form_name' => $formName, 'field_path' => $formPath, 'visible' => '=false']
        );

        $this->assertSame($formView, $view->vars['form']);
        $this->assertFalse($formView->isRendered());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(EmbedFormFieldType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
