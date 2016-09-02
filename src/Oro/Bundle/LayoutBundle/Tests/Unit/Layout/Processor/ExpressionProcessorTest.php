<?php

namespace LayoutBundle\Tests\Unit\Layout\Processor;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\Action;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\ExpressionLanguage\ExpressionManipulator;
use Oro\Bundle\LayoutBundle\Layout\Processor\ExpressionProcessor;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonExpressionEncoder;

class ExpressionProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionLanguage|\PHPUnit_Framework_MockObject_MockObject */
    protected $expressionLanguage;

    /** @var JsonExpressionEncoder|\PHPUnit_Framework_MockObject_MockObject */
    protected $encoder;

    /** @var ExpressionProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();

        $encoderRegistry = $this
            ->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Encoder\ExpressionEncoderRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->encoder = new JsonExpressionEncoder(new ExpressionManipulator());
        $encoderRegistry->expects($this->any())
            ->method('getEncoder')
            ->with('json')
            ->will($this->returnValue($this->encoder));

        $this->processor = new ExpressionProcessor(
            $this->expressionLanguage,
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
        $data = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        $trueExpr = new ParsedExpression('true', new ConstantNode(true));

        $classAttr = new OptionValueBag();
        $classAttr->add('=context["css_class"]');
        $expectedClassAttr = new OptionValueBag();
        $expectedClassAttr->add('test_class');

        $values['expr_object'] = $trueExpr;
        $values['dependent_expr'] = '=true == expr_string';
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['data-scalar'] = 'foo';
        $values['attr']['data-expr'] = '=true';
        $values['attr']['class'] = $classAttr;
        $values['label_attr']['enabled'] = '=true';
        $values['array_with_expr'] = ['item1' => 'val1', 'item2' => '=true'];

        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertSame(
            true,
            $values['expr_object'],
            'Failed asserting that an expression is evaluated'
        );
        $this->assertSame(
            true,
            $values['dependent_expr'],
            'Failed asserting that dependent expression is parsed and evaluated'
        );
        $this->assertSame(
            true,
            $values['expr_string'],
            'Failed asserting that an expression is parsed and evaluated'
        );
        $this->assertSame(
            '=true',
            $values['not_expr_string'],
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
            'Failed asserting that an expression in "attr" is parsed and evaluated'
        );
        $this->assertSame(
            'foo',
            $values['attr']['data-scalar'],
            'Failed asserting that "attr.data-scalar" exists'
        );
        $this->assertSame(
            true,
            $values['attr']['data-expr'],
            'Failed asserting that "attr.data-expr" is parsed and evaluated'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $values['attr']['class'],
            'Failed asserting that "attr.class" is parsed and evaluated'
        );
        $this->assertSame(
            true,
            $values['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is parsed and evaluated'
        );
        $this->assertSame(
            ['item1' => 'val1', 'item2' => true],
            $values['array_with_expr'],
            'Failed asserting that an expression is parsed and evaluated in nested array'
        );
    }


    /**
     * @expectedException \Oro\Bundle\LayoutBundle\Exception\CircularReferenceException
     * @expectedExceptionMessage Circular reference "first > second > third > first" on expression "true == first".
     */
    public function testProcessExpressionsWithCircularReference()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        $values['first'] = '=second';
        $values['second'] = '=third';
        $values['third'] = '=true == first';

        $this->processor->processExpressions($values, $context, $data, true, null);

    }


    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "data" and "context" should not be used as value keys.
     */
    public function testProcessExpressionsWithDataKey()
    {
        $context = new LayoutContext();
        $data = $this->getMock('Oro\Component\Layout\DataAccessorInterface');
        $values['data'] = 'test';

        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "data" and "context" should not be used as value keys.
     */
    public function testProcessExpressionsWithContextKey()
    {
        $context = new LayoutContext();
        $data = $this->getMock('Oro\Component\Layout\DataAccessorInterface');
        $values['context'] = 'test';

        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    public function testProcessExpressionsDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();
        $data = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        $expr = $this->getMockBuilder(ParsedExpression::class)
            ->disableOriginalConstructor()
            ->getMock();

        $values['expr_object'] = $expr;
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['label_attr']['enabled'] = '=true';

        $initialVars = $values;

        $this->processor->processExpressions($values, $context, $data, false, null);

        $this->assertSame($initialVars, $values);
    }

    public function testProcessExpressionsEncodesAllExpressions()
    {
        $context = new LayoutContext();
        $context->set('expressions_evaluate_deferred', true);
        $data = $this->getMock('Oro\Component\Layout\DataAccessorInterface');
        $trueExpr = new ParsedExpression('true', new ConstantNode(true));

        $classAttr = new OptionValueBag();
        $classAttr->add('=context["css_class"]');

        $values['expr_object'] = $trueExpr;
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['class'] = $classAttr;
        $values['label_attr']['enabled'] = '=true';

        $this->processor->processExpressions($values, $context, $data, false, 'json');

        $trueExprJson = __DIR__.'/data/true_expression.json';

        $this->assertJsonStringEqualsJsonFile(
            $trueExprJson,
            $values['expr_object'],
            'Failed asserting that an expression is encoded'
        );
        $this->assertJsonStringEqualsJsonFile(
            $trueExprJson,
            $values['expr_string'],
            'Failed asserting that an expression is parsed and encoded'
        );
        $this->assertSame(
            '=true',
            $values['not_expr_string'],
            'Failed asserting that a backslash at the begin of the array key is removed'
        );
        $this->assertSame(
            123,
            $values['scalar'],
            'Failed asserting that a scalar value is not changed'
        );
        $this->assertJsonStringEqualsJsonFile(
            $trueExprJson,
            $values['attr']['enabled'],
            'Failed asserting that an expression in "attr" is parsed and encoded'
        );
        $actualClassAttr = $values['attr']['class'];
        $this->assertInstanceOf(OptionValueBag::class, $actualClassAttr);
        $actualClassActions = $actualClassAttr->all();
        $this->assertArrayHasKey(0, $actualClassActions);
        $actualAddAction = $actualClassActions[0];
        $this->assertInstanceOf(Action::class, $actualAddAction);
        $this->assertJsonStringEqualsJsonFile(
            __DIR__.'/data/class_expression.json',
            $actualAddAction->getArgument(0),
            'Failed asserting that "attr.class" is parsed and encoded'
        );
        $this->assertJsonStringEqualsJsonFile(
            $trueExprJson,
            $values['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is parsed and encoded'
        );
    }
}
