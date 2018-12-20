<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\Action;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use Oro\Component\Layout\ExpressionLanguage\Encoder\JsonExpressionEncoder;
use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionLanguage|\PHPUnit\Framework\MockObject\MockObject */
    protected $expressionLanguage;

    /** @var JsonExpressionEncoder|\PHPUnit\Framework\MockObject\MockObject */
    protected $encoder;

    /** @var ExpressionProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();

        /** @var ExpressionEncoderRegistry|\PHPUnit\Framework\MockObject\MockObject $encoderRegistry */
        $encoderRegistry = $this->createMock(ExpressionEncoderRegistry::class);

        $this->encoder = new JsonExpressionEncoder(new ExpressionManipulator());

        $encoderRegistry->expects($this->any())
            ->method('get')
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
        $data = $this->createMock('Oro\Component\Layout\DataAccessorInterface');

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
     * @expectedException \Oro\Component\Layout\Exception\CircularReferenceException
     * @expectedExceptionMessage Circular reference "first > second > third > first" on expression "true == first".
     */
    public function testProcessExpressionsWithCircularReference()
    {
        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data = $this->createMock('Oro\Component\Layout\DataAccessorInterface');

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
        $data = $this->createMock('Oro\Component\Layout\DataAccessorInterface');
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
        $data = $this->createMock('Oro\Component\Layout\DataAccessorInterface');
        $values['context'] = 'test';

        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    public function testProcessExpressionsDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();
        $data = $this->createMock('Oro\Component\Layout\DataAccessorInterface');

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
        $data = $this->createMock('Oro\Component\Layout\DataAccessorInterface');
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
        
        $trueExprJson = __DIR__.'/Fixtures/true_expression.json';

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
            __DIR__.'/Fixtures/class_expression.json',
            $actualAddAction->getArgument(0),
            'Failed asserting that "attr.class" is parsed and encoded'
        );
        $this->assertJsonStringEqualsJsonFile(
            $trueExprJson,
            $values['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is parsed and encoded'
        );
    }

    public function testProcessExpressionsWithVisibleFalse()
    {
        $context = new LayoutContext();
        $data = $this->createMock('Oro\Component\Layout\DataAccessorInterface');

        $values['expr_string'] = '=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['data-scalar'] = 'foo';
        $values['array_with_expr'] = ['item1' => 'val1', 'item2' => '=true'];
        $values['visible'] = '=false';

        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertFalse($values['visible'], 'Failed asserting that an expression is evaluated');

        $this->assertNull($values['expr_string']);
        $this->assertSame(123, $values['scalar']);
        $this->assertNull($values['attr']['enabled']);
        $this->assertSame('foo', $values['attr']['data-scalar']);
        $this->assertSame(['item1' => 'val1', 'item2' => null], $values['array_with_expr']);
    }
}
