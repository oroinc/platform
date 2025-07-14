<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ClassAttributeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use Oro\Component\Layout\ExpressionLanguage\Encoder\JsonExpressionEncoder;
use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;
use PHPUnit\Framework\TestCase;

class ClassAttributeExtensionTest extends TestCase
{
    private ClassAttributeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $encoderRegistry = $this->createMock(ExpressionEncoderRegistry::class);
        $encoderRegistry->expects($this->any())
            ->method('get')
            ->with('json')
            ->willReturn(new JsonExpressionEncoder(new ExpressionManipulator()));

        $this->extension = new ClassAttributeExtension($encoderRegistry);
    }

    public function testGetExtendedType(): void
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testFinishViewEvaluatesClassAttrExpression(): void
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())
            ->method('getContext')
            ->willReturn($context);
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('test_class');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = true;
        $this->extension->finishView($view, $block);

        $this->assertEquals('test_class', $view->vars['attr']['class']);
    }

    public function testFinishViewEvaluatesClassAttrExpressionAndRemovesEmptyClass(): void
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())
            ->method('getContext')
            ->willReturn($context);
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = true;
        $this->extension->finishView($view, $block);

        $this->assertArrayNotHasKey('class', $view->vars['attr']);
    }

    public function testFinishViewDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet(): void
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())
            ->method('getContext')
            ->willReturn($context);
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('test_class');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = false;
        $this->extension->finishView($view, $block);

        $this->assertSame($classAttr, $view->vars['attr']['class']);
    }

    public function testFinishViewEncodesClassAttrExpression(): void
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())
            ->method('getContext')
            ->willReturn($context);
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('test_class');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'json';
        $this->extension->finishView($view, $block);

        $this->assertEquals('{"@actions":[{"name":"add","args":["test_class"]}]}', $view->vars['attr']['class']);
    }

    public function testFinishViewRemovesEmptyClassWhenEncodingEnabled(): void
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())
            ->method('getContext')
            ->willReturn($context);
        $view = new BlockView();

        $view->vars['attr']['class'] = '';

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'json';
        $this->extension->finishView($view, $block);

        $this->assertArrayNotHasKey('class', $view->vars['attr']);
    }
}
