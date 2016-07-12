<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\ConfigExpression\Func;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\ExpressionLanguage\ExpressionManipulator;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ClassAttributeExtension;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class ClassAttributeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ClassAttributeExtension */
    protected $extension;

    protected function setUp()
    {
        $encoderRegistry = $this
            ->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $encoderRegistry->expects($this->any())
            ->method('getEncoder')
            ->with('json')
            ->will($this->returnValue(new JsonConfigExpressionEncoder(new ExpressionManipulator())));

        $this->extension = new ClassAttributeExtension($encoderRegistry);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testFinishViewEvaluatesClassAttrExpression()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('test_class');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = true;
        $this->extension->finishView($view, $block, []);

        $this->assertEquals('test_class', $view->vars['attr']['class']);
    }

    public function testFinishViewEvaluatesClassAttrExpressionAndRemovesEmptyClass()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = true;
        $this->extension->finishView($view, $block, []);

        $this->assertArrayNotHasKey('class', $view->vars['attr']);
    }

    public function testFinishViewDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('test_class');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = false;
        $this->extension->finishView($view, $block, []);

        $this->assertSame($classAttr, $view->vars['attr']['class']);
    }

    public function testFinishViewEncodesClassAttrExpression()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $view = new BlockView();

        $classAttr = new OptionValueBag();
        $classAttr->add('test_class');

        $view->vars['attr']['class'] = $classAttr;

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'json';
        $this->extension->finishView($view, $block, []);

        $this->assertEquals('{"@actions":[{"name":"add","args":["test_class"]}]}', $view->vars['attr']['class']);
    }

    public function testFinishViewRemovesEmptyClassWhenEncodingEnabled()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $view = new BlockView();

        $view->vars['attr']['class'] = '';

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'json';
        $this->extension->finishView($view, $block, []);

        $this->assertArrayNotHasKey('class', $view->vars['attr']);
    }
}
