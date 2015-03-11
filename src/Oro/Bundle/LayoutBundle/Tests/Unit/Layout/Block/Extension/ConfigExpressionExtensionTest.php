<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ConfigExpressionExtension;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class ConfigExpressionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $expressionAssembler;

    /** @var ConfigExpressionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->expressionAssembler = $this->getMock('Oro\Component\ConfigExpression\AssemblerInterface');
        $this->container           = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->container->expects($this->any())
            ->method('get')
            ->with('json_encoder_service')
            ->will($this->returnValue(new JsonConfigExpressionEncoder()));

        $this->extension = new ConfigExpressionExtension(
            $this->expressionAssembler,
            $this->container,
            ['json' => 'json_encoder_service']
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testFinishViewEvaluatesAllExpressions()
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

        $view->vars['expr_object']           = $expr;
        $view->vars['expr_array']            = ['@true' => null];
        $view->vars['not_expr_array']        = ['\@true' => null];
        $view->vars['scalar']                = 123;
        $view->vars['attr']['enabled']       = ['@true' => null];
        $view->vars['attr']['data-scalar']   = 'foo';
        $view->vars['attr']['data-expr']     = ['@true' => null];
        $view->vars['label_attr']['enabled'] = ['@true' => null];
        $view->vars['array_with_expr']       = ['item1' => 'val1', 'item2' => ['@true' => null]];

        $expr->expects($this->once())
            ->method('evaluate')
            ->with(['context' => $context, 'data' => $data])
            ->will($this->returnValue(true));

        $this->expressionAssembler->expects($this->exactly(5))
            ->method('assemble')
            ->with(['@true' => null])
            ->will($this->returnValue(new Condition\True()));

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

    public function testFinishViewDoNothingIfEvaluationIfExpressionsDisabledAndEncodingIsNotSet()
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

        $view->vars['expr_object']           = $expr;
        $view->vars['expr_array']            = ['@true' => null];
        $view->vars['not_expr_array']        = ['\@true' => null];
        $view->vars['scalar']                = 123;
        $view->vars['attr']['enabled']       = ['@true' => null];
        $view->vars['label_attr']['enabled'] = ['@true' => null];

        $this->expressionAssembler->expects($this->exactly(3))
            ->method('assemble')
            ->with(['@true' => null])
            ->will($this->returnValue(new Condition\True()));

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
        $this->assertSame(
            '{"@true":null}',
            $view->vars['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and encoded'
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The expression encoder for "unknown" formatting was not found. Check that the appropriate encoder service is registered in the container and marked by tag "oro_layout.expression.encoder".
     */
    // @codingStandardsIgnoreEnd
    public function testFinishViewThrowsExceptionIfEncoderDoesNotExist()
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
        $expr->expects($this->never())
            ->method('toArray');

        $view->vars['expr_object'] = $expr;

        $context['expressions_evaluate'] = false;
        $context['expressions_encoding'] = 'unknown';
        $this->extension->finishView($view, $block, []);
    }
}
