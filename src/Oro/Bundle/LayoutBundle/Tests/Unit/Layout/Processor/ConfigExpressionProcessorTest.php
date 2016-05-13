<?php

namespace LayoutBundle\Tests\Unit\Layout\Processor;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\AssemblerInterface;
use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Processor\ConfigExpressionProcessor;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class ConfigExpressionProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var AssemblerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $expressionAssembler;

    /** @var ConfigExpressionProcessor */
    protected $processor;

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

        $this->processor = new ConfigExpressionProcessor(
            $this->expressionAssembler,
            $encoderRegistry
        );
    }
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessExpressionsEvaluatesAllExpressions()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data  = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

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

        $values['expr_object']           = $expr;
        $values['expr_array']            = ['@true' => null];
        $values['not_expr_array']        = ['\@true' => null];
        $values['scalar']                = 123;
        $values['attr']['enabled']       = ['@true' => null];
        $values['attr']['data-scalar']   = 'foo';
        $values['attr']['data-expr']     = ['@true' => null];
        $values['attr']['class']         = $classAttr;
        $values['label_attr']['enabled'] = ['@true' => null];
        $values['array_with_expr']       = ['item1' => 'val1', 'item2' => ['@true' => null]];

        $this->expressionAssembler->expects($this->exactly(6))
            ->method('assemble')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true' => null], new Condition\TrueCondition()],
                        [['@value' => ['$context.css_class']], $classExpr]
                    ]
                )
            );

        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertSame(
            true,
            $values['expr_object'],
            'Failed asserting that an expression is evaluated'
        );
        $this->assertSame(
            true,
            $values['expr_array'],
            'Failed asserting that an expression is assembled and evaluated'
        );
        $this->assertSame(
            ['@true' => null],
            $values['not_expr_array'],
            'Failed asserting that a backslash at the begin of the array key is removed'
        );
        $this->assertSame(
            123,
            $values['scalar'],
            'Failed asserting that a scalar value is not changed'
        );
        $this->assertSame(
            true,
            $values['attr']['enabled'],
            'Failed asserting that an expression in "attr" is assembled and evaluated'
        );
        $this->assertSame(
            'foo',
            $values['attr']['data-scalar'],
            'Failed asserting that "attr.data-scalar" exists'
        );
        $this->assertSame(
            true,
            $values['attr']['data-expr'],
            'Failed asserting that "attr.data-expr" is assembled and evaluated'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $values['attr']['class'],
            'Failed asserting that "attr.class" is assembled and evaluated'
        );
        $this->assertSame(
            true,
            $values['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and evaluated'
        );
        $this->assertSame(
            ['item1' => 'val1', 'item2' => true],
            $values['array_with_expr'],
            'Failed asserting that an expression is assembled and evaluated in nested array'
        );
    }

    public function testProcessExpressionsDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();
        $data  = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        $expr = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $expr->expects($this->never())
            ->method('evaluate');
        $expr->expects($this->never())
            ->method('toArray');

        $values['expr_object']           = $expr;
        $values['expr_array']            = ['@true' => null];
        $values['not_expr_array']        = ['\@true' => null];
        $values['scalar']                = 123;
        $values['attr']['enabled']       = ['@true' => null];
        $values['label_attr']['enabled'] = ['@true' => null];

        $this->expressionAssembler->expects($this->never())
            ->method('assemble');

        $initialVars = $values;

        $this->processor->processExpressions($values, $context, $data, false, null);

        $this->assertSame($initialVars, $values);
    }

    public function testProcessExpressionsEncodesAllExpressions()
    {
        $context = new LayoutContext();
        $context->set('expressions_evaluate_deferred', true);
        $data    = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

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

        $values['expr_object']           = $expr;
        $values['expr_array']            = ['@true' => null];
        $values['not_expr_array']        = ['\@true' => null];
        $values['scalar']                = 123;
        $values['attr']['enabled']       = ['@true' => null];
        $values['attr']['class']         = $classAttr;
        $values['label_attr']['enabled'] = ['@true' => null];

        $this->expressionAssembler->expects($this->exactly(4))
            ->method('assemble')
            ->will(
                $this->returnValueMap(
                    [
                        [['@true' => null], new Condition\TrueCondition()],
                        [['@value' => ['$context.css_class']], $classExpr]
                    ]
                )
            );

        $this->processor->processExpressions($values, $context, $data, false, 'json');

        $this->assertSame(
            '{"@true":null}',
            $values['expr_object'],
            'Failed asserting that an expression is encoded'
        );
        $this->assertSame(
            '{"@true":null}',
            $values['expr_array'],
            'Failed asserting that an expression is assembled and encoded'
        );
        $this->assertSame(
            ['@true' => null],
            $values['not_expr_array'],
            'Failed asserting that a backslash at the begin of the array key is removed'
        );
        $this->assertSame(
            123,
            $values['scalar'],
            'Failed asserting that a scalar value is not changed'
        );
        $this->assertSame(
            '{"@true":null}',
            $values['attr']['enabled'],
            'Failed asserting that an expression in "attr" is assembled and encoded'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $values['attr']['class'],
            'Failed asserting that "attr.class" is assembled and encoded'
        );
        $this->assertSame(
            '{"@true":null}',
            $values['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is assembled and encoded'
        );
    }
}
