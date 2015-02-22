<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\ConfigExpressionExtension;
use Oro\Bundle\LayoutBundle\Layout\Extension\JsonConfigExpressionEncoder;

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

    public function testDefaultValuesAfterConfigureContext()
    {
        $context = new LayoutContext();

        $this->extension->configureContext($context);
        $context->resolve();

        $this->assertTrue($context['expressions.evaluate']);
        $this->assertFalse(isset($context['expressions.encoding']));
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();

        $context['expressions.evaluate'] = false;
        $context['expressions.encoding'] = 'json';

        $this->extension->configureContext($context);
        $context->resolve();

        $this->assertFalse($context['expressions.evaluate']);
        $this->assertEquals('json', $context['expressions.encoding']);
    }

    public function testFinishViewEvaluatesAllExpressions()
    {
        $context = new LayoutContext();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $view = new BlockView();

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->once())
            ->method('evaluate')
            ->with(['context' => $context])
            ->will($this->returnValue(true));

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

        $context['expressions.evaluate'] = true;
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
            true,
            $view->vars['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and evaluated'
        );
    }

    public function testFinishViewDoNothingIfEvaluationIfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
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

        $context['expressions.evaluate'] = false;
        $this->extension->finishView($view, $block, []);

        $this->assertSame($initialVars, $view->vars);
    }

    public function testFinishViewEncodesAllExpressions()
    {
        $context = new LayoutContext();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
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

        $context['expressions.evaluate'] = false;
        $context['expressions.encoding'] = 'json';
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
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $view = new BlockView();

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->never())
            ->method('toArray');

        $view->vars['expr_object'] = $expr;

        $context['expressions.evaluate'] = false;
        $context['expressions.encoding'] = 'unknown';
        $this->extension->finishView($view, $block, []);
    }
}
