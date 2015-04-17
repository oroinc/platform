<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ConfigExpressionExtension;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;
use Oro\Component\PropertyAccess\PropertyPath;

class ConfigExpressionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $expressionAssembler;

    /** @var ConfigExpressionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->expressionAssembler = $this->getMock('Oro\Component\ConfigExpression\AssemblerInterface');

        $encoderRegistry = $this
            ->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $encoderRegistry->expects($this->any())
            ->method('getEncoder')
            ->with('json')
            ->will($this->returnValue(new JsonConfigExpressionEncoder()));

        $this->extension = new ConfigExpressionExtension(
            $this->expressionAssembler,
            $encoderRegistry
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testFinishViewEvaluatesAllExpressions()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data  = $this->getMock('Oro\Component\Layout\DataAccessorInterface');
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $view = new BlockView();

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->once())
            ->method('evaluate')
            ->with(['context' => $context, 'data' => $data])
            ->will($this->returnValue(true));

        $classExpr = new Func\GetValue();
        $classExpr->initialize([new PropertyPath('context.css_class')]);
        $classExpr->setContextAccessor(new ContextAccessor());
        $classAttr = new OptionValueBag();
        $classAttr->add(['@value' => ['$context.css_class']]);
        $expectedClassAttr = new OptionValueBag();
        $expectedClassAttr->add('test_class');

        $view->vars['expr_object']           = $expr;
        $view->vars['expr_array']            = ['@true' => null];
        $view->vars['not_expr_array']        = ['\@true' => null];
        $view->vars['scalar']                = 123;
        $view->vars['attr']['enabled']       = ['@true' => null];
        $view->vars['attr']['data-scalar']   = 'foo';
        $view->vars['attr']['data-expr']     = ['@true' => null];
        $view->vars['attr']['class']         = $classAttr;
        $view->vars['label_attr']['enabled'] = ['@true' => null];
        $view->vars['array_with_expr']       = ['item1' => 'val1', 'item2' => ['@true' => null]];

        $this->expressionAssembler->expects($this->exactly(6))
            ->method('assemble')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true' => null], new Condition\True()],
                        [['@value' => ['$context.css_class']], $classExpr]
                    ]
                )
            );

        $context['expressions_evaluate'] = true;
        $this->extension->finishView($view, $block, []);

        $this->assertSame(
            true,
            $view->vars['expr_object'],
            'Failed asserting that an expression is evaluated'
        );
        $this->assertSame(
            true,
            $view->vars['expr_array'],
            'Failed asserting that an expression is assembled and evaluated'
        );
        $this->assertSame(
            ['@true' => null],
            $view->vars['not_expr_array'],
            'Failed asserting that a backslash at the begin of the array key is removed'
        );
        $this->assertSame(
            123,
            $view->vars['scalar'],
            'Failed asserting that a scalar value is not changed'
        );
        $this->assertSame(
            true,
            $view->vars['attr']['enabled'],
            'Failed asserting that an expression in "attr" is assembled and evaluated'
        );
        $this->assertSame(
            'foo',
            $view->vars['attr']['data-scalar'],
            'Failed asserting that "attr.data-scalar" exists'
        );
        $this->assertSame(
            true,
            $view->vars['attr']['data-expr'],
            'Failed asserting that "attr.data-expr" is assembled and evaluated'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $view->vars['attr']['class'],
            'Failed asserting that "attr.class" is assembled and evaluated'
        );
        $this->assertSame(
            true,
            $view->vars['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and evaluated'
        );
        $this->assertSame(
            ['item1' => 'val1', 'item2' => true],
            $view->vars['array_with_expr'],
            'Failed asserting that an expression is assembled and evaluated in nested array'
        );
    }

    public function testFinishViewDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->never())
            ->method('getData');
        $view = new BlockView();

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->never())
            ->method('evaluate');
        $expr->expects($this->never())
            ->method('toArray');

        $view->vars['expr_object']           = $expr;
        $view->vars['expr_array']            = ['@true' => null];
        $view->vars['not_expr_array']        = ['\@true' => null];
        $view->vars['scalar']                = 123;
        $view->vars['attr']['enabled']       = ['@true' => null];
        $view->vars['label_attr']['enabled'] = ['@true' => null];

        $this->expressionAssembler->expects($this->never())
            ->method('assemble');

        $initialVars = $view->vars;

        $context['expressions_evaluate'] = false;
        $this->extension->finishView($view, $block, []);

        $this->assertSame($initialVars, $view->vars);
    }

    public function testFinishViewEncodesAllExpressions()
    {
        $context = new LayoutContext();
        $data    = $this->getMock('Oro\Component\Layout\DataAccessorInterface');
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $block->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $view = new BlockView();

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue(['@true' => null]));

        $classExpr = new Func\GetValue();
        $classExpr->initialize([new PropertyPath('context.css_class')]);
        $classAttr = new OptionValueBag();
        $classAttr->add(['@value' => ['$context.css_class']]);
        $expectedClassAttr = new OptionValueBag();
        $expectedClassAttr->add('{"@value":{"parameters":["$context.css_class"]}}');

        $view->vars['expr_object']           = $expr;
        $view->vars['expr_array']            = ['@true' => null];
        $view->vars['not_expr_array']        = ['\@true' => null];
        $view->vars['scalar']                = 123;
        $view->vars['attr']['enabled']       = ['@true' => null];
        $view->vars['attr']['class']         = $classAttr;
        $view->vars['label_attr']['enabled'] = ['@true' => null];

        $this->expressionAssembler->expects($this->exactly(4))
            ->method('assemble')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true' => null], new Condition\True()],
                        [['@value' => ['$context.css_class']], $classExpr]
                    ]
                )
            );

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'json';
        $this->extension->finishView($view, $block, []);

        $this->assertSame(
            '{"@true":null}',
            $view->vars['expr_object'],
            'Failed asserting that an expression is encoded'
        );
        $this->assertSame(
            '{"@true":null}',
            $view->vars['expr_array'],
            'Failed asserting that an expression is assembled and encoded'
        );
        $this->assertSame(
            ['@true' => null],
            $view->vars['not_expr_array'],
            'Failed asserting that a backslash at the begin of the array key is removed'
        );
        $this->assertSame(
            123,
            $view->vars['scalar'],
            'Failed asserting that a scalar value is not changed'
        );
        $this->assertSame(
            '{"@true":null}',
            $view->vars['attr']['enabled'],
            'Failed asserting that an expression in "attr" is assembled and encoded'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $view->vars['attr']['class'],
            'Failed asserting that "attr.class" is assembled and encoded'
        );
        $this->assertSame(
            '{"@true":null}',
            $view->vars['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and encoded'
        );
    }
}
