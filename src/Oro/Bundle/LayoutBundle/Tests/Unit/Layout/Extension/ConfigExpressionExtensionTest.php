<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;
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

        $this->assertTrue($context[ConfigExpressionExtension::PARAM_EVALUATE]);
        $this->assertFalse(isset($context[ConfigExpressionExtension::PARAM_ENCODING]));
    }

    public function testConfigureContext()
    {
        $context = new LayoutContext();

        $context[ConfigExpressionExtension::PARAM_EVALUATE] = false;
        $context[ConfigExpressionExtension::PARAM_ENCODING] = 'json';

        $this->extension->configureContext($context);
        $context->resolve();

        $this->assertFalse($context[ConfigExpressionExtension::PARAM_EVALUATE]);
        $this->assertEquals('json', $context[ConfigExpressionExtension::PARAM_ENCODING]);
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

        $expr->expects($this->once())
            ->method('evaluate')
            ->with(['context' => $context, 'data' => $data, 'scalar' => 'foo', 'expr' => true])
            ->will($this->returnValue(true));

        $this->expressionAssembler->expects($this->exactly(4))
            ->method('assemble')
            ->with(['@true' => null])
            ->will($this->returnValue(new Condition\True()));

        $context[ConfigExpressionExtension::PARAM_EVALUATE] = true;
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
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\NoSuchPropertyException
     * @expectedExceptionMessage The key "$expr" does exist in an array.
     */
    public function testFinishViewEvaluatesExpressionCycledToItself()
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

        $view->vars['attr']['data-expr'] = ['@trim' => '$expr'];

        $expr = new Func\Trim();
        $expr->initialize([new PropertyPath('$expr')]);
        $expr->setContextAccessor(new ContextAccessor());

        $this->expressionAssembler->expects($this->once())
            ->method('assemble')
            ->with(['@trim' => '$expr'])
            ->will($this->returnValue($expr));

        $context[ConfigExpressionExtension::PARAM_EVALUATE] = true;
        $this->extension->finishView($view, $block, []);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Circular reference in an expression for variable "data-expr2" of block "test_block". Max nesting level is 5.
     */
    // @codingStandardsIgnoreEnd
    public function testFinishViewEvaluatesCycledExpressions()
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
        $view             = new BlockView();
        $view->vars['id'] = 'test_block';

        $view->vars['attr']['data-expr1'] = ['@trim' => '$expr2'];
        $view->vars['attr']['data-expr2'] = ['@trim' => '$expr1'];

        $expr1 = new Func\Trim();
        $expr1->initialize([new PropertyPath('$expr2')]);
        $expr1->setContextAccessor(new ContextAccessor());

        $expr2 = new Func\Trim();
        $expr2->initialize([new PropertyPath('$expr1')]);
        $expr2->setContextAccessor(new ContextAccessor());

        $this->expressionAssembler->expects($this->exactly(2))
            ->method('assemble')
            ->will(
                $this->returnValueMap(
                    [
                        [['@trim' => '$expr2'], $expr1],
                        [['@trim' => '$expr1'], $expr2]
                    ]
                )
            );

        $context[ConfigExpressionExtension::PARAM_EVALUATE] = true;
        $this->extension->finishView($view, $block, []);
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

        $context[ConfigExpressionExtension::PARAM_EVALUATE] = false;
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

        $context[ConfigExpressionExtension::PARAM_EVALUATE] = false;
        $context[ConfigExpressionExtension::PARAM_ENCODING] = 'json';
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

        $context[ConfigExpressionExtension::PARAM_EVALUATE] = false;
        $context[ConfigExpressionExtension::PARAM_ENCODING] = 'unknown';
        $this->extension->finishView($view, $block, []);
    }
}
