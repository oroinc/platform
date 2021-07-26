<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\Action;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\Exception\CircularReferenceException;
use Oro\Component\Layout\ExpressionLanguage\ClosureWithExtraParams;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use Oro\Component\Layout\ExpressionLanguage\Encoder\JsonExpressionEncoder;
use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExpressionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionLanguage|\PHPUnit\Framework\MockObject\MockObject */
    protected $expressionLanguage;

    /** @var ExpressionProcessor */
    protected $processor;

    protected function setUp(): void
    {
        $this->expressionLanguage = new ExpressionLanguage();

        $this->processor = $this->createExpressionProcessor();
    }

    protected function createExpressionProcessor(): ExpressionProcessor
    {
        return new ExpressionProcessor(
            $this->expressionLanguage,
            $this->createEncoderRegistry()
        );
    }

    protected function createEncoderRegistry(): ExpressionEncoderRegistry
    {
        $encoderRegistry = $this->createMock(ExpressionEncoderRegistry::class);
        $encoderRegistry->expects($this->any())
            ->method('get')
            ->with('json')
            ->willReturn(new JsonExpressionEncoder(new ExpressionManipulator()));

        return $encoderRegistry;
    }

    protected function createOptionValueBag(string $expr): OptionValueBag
    {
        $optionValueBag = new OptionValueBag();
        $optionValueBag->add($expr);

        return $optionValueBag;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessExpressionsEvaluatesAllExpressions()
    {
        $values['expr_object'] = new ParsedExpression('true', new ConstantNode(true));
        $values['expr_closure'] = function () {
            return true;
        };
        $values['expr_closure_with_extra_params'] = new ClosureWithExtraParams(
            function ($context, $data, $attr) {
                return $attr['data-scalar'];
            },
            ['attr'],
            'attr["data-scalar"]'
        );
        $values['dependent_expr'] = '=true == expr_string';
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['data-scalar'] = 'foo';
        $values['attr']['data-expr'] = '=true';
        $values['attr']['class'] = $this->createOptionValueBag('=context["css_class"]');
        $values['label_attr']['enabled'] = '=true';
        $values['array_with_expr'] = ['item1' => 'val1', 'item2' => '=true'];

        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertTrue(
            $values['expr_object'],
            'Failed asserting that an expression is evaluated'
        );
        $this->assertTrue(
            $values['expr_closure'],
            'Failed asserting that a closure is evaluated'
        );
        $this->assertEquals(
            'foo',
            $values['expr_closure_with_extra_params'],
            'Failed asserting that a closure with extra params is evaluated'
        );
        $this->assertTrue(
            $values['dependent_expr'],
            'Failed asserting that dependent expression is parsed and evaluated'
        );
        $this->assertTrue(
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
        $this->assertTrue(
            $values['attr']['enabled'],
            'Failed asserting that an expression in "attr" is parsed and evaluated'
        );
        $this->assertSame(
            'foo',
            $values['attr']['data-scalar'],
            'Failed asserting that "attr.data-scalar" exists'
        );
        $this->assertTrue(
            $values['attr']['data-expr'],
            'Failed asserting that "attr.data-expr" is parsed and evaluated'
        );
        $this->assertEquals(
            $this->createOptionValueBag('test_class'),
            $values['attr']['class'],
            'Failed asserting that "attr.class" is parsed and evaluated'
        );
        $this->assertTrue(
            $values['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is parsed and evaluated'
        );
        $this->assertSame(
            ['item1' => 'val1', 'item2' => true],
            $values['array_with_expr'],
            'Failed asserting that an expression is parsed and evaluated in nested array'
        );
    }

    public function testProcessExpressionsWithCircularReference()
    {
        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage(
            'Circular reference "first > second > third > first" on expression "true == first".'
        );

        $values['first'] = '=second';
        $values['second'] = '=third';
        $values['third'] = '=true == first';

        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    public function testProcessExpressionsWithDataKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"data" should not be used as value key.');

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $values['data'] = 'test';
        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    public function testProcessExpressionsWithContextKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"context" should not be used as value key.');

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $values['context'] = 'test';
        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    public function testProcessExpressionsDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $values['expr_object'] = $this->createMock(ParsedExpression::class);
        $values['expr_closure'] = function () {
            return true;
        };
        $values['expr_closure_with_extra_params'] = new ClosureWithExtraParams(
            function ($context, $data, $attr) {
                return $attr['data-scalar'];
            },
            ['attr'],
            'attr["data-scalar"]'
        );
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['label_attr']['enabled'] = '=true';

        $initialVars = $values;

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, false, null);

        $this->assertSame($initialVars, $values);
    }

    public function testProcessExpressionsEncodesAllExpressions()
    {
        $values['expr_object'] = new ParsedExpression('true', new ConstantNode(true));
        $values['expr_closure'] = function () {
            return true;
        };
        $values['expr_closure_with_extra_params'] = new ClosureWithExtraParams(
            function ($context, $data, $scalar) {
                return $scalar;
            },
            ['scalar'],
            'scalar'
        );
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['class'] = $this->createOptionValueBag('=context["css_class"]');
        $values['label_attr']['enabled'] = '=true';

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, false, 'json');

        $trueExprJson = __DIR__ . '/Fixtures/true_expression.json';

        $this->assertJsonStringEqualsJsonFile(
            $trueExprJson,
            $values['expr_object'],
            'Failed asserting that an expression is encoded'
        );
        $this->assertTrue(
            $values['expr_closure'],
            'Failed asserting that a closure is encoded'
        );
        $this->assertSame(
            123,
            $values['expr_closure_with_extra_params'],
            'Failed asserting that a closure with extra params is encoded'
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
            __DIR__ . '/Fixtures/class_expression.json',
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
        $values['expr_object'] = new ParsedExpression('true', new ConstantNode(true));
        $values['expr_closure'] = function () {
            return true;
        };
        $values['expr_closure_with_extra_params'] = new ClosureWithExtraParams(
            function ($context, $data, $attr) {
                return $attr['data-scalar'];
            },
            ['attr'],
            'attr["data-scalar"]'
        );
        $values['expr_string'] = '=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['data-scalar'] = 'foo';
        $values['array_with_expr'] = ['item1' => 'val1', 'item2' => '=true'];
        $values['visible'] = '=false';

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertFalse($values['visible'], 'Failed asserting that an expression is evaluated');

        $this->assertNull($values['expr_object']);
        $this->assertNull($values['expr_closure']);
        $this->assertNull($values['expr_closure_with_extra_params']);
        $this->assertNull($values['expr_string']);
        $this->assertSame(123, $values['scalar']);
        $this->assertNull($values['attr']['enabled']);
        $this->assertSame('foo', $values['attr']['data-scalar']);
        $this->assertSame(['item1' => 'val1', 'item2' => null], $values['array_with_expr']);
    }

    public function testEvaluateStringExpressionWhenValueUsedInThisExpressionDoesNotExist()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Variable "expr_1" is not valid around position 1 for expression `expr_1`. Did you mean "expr_2"?'
        );

        $values['dependent_expr'] = '=expr_1';
        $values['scalar'] = 123;
        $values['expr_2'] = 'val';

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    public function testEvaluateClosureWithExtraParamsWhenValueUsedInThisExpressionDoesNotExist()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Variable "expr_1" is not valid around position 0 for expression `expr_1`. Did you mean "expr_2"?'
        );

        $values['expr_closure_with_extra_params'] = new ClosureWithExtraParams(
            function ($context, $data, $val) {
                return $val;
            },
            ['expr_1'],
            'expr_1'
        );
        $values['scalar'] = 123;
        $values['expr_2'] = 'val';

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);
    }

    /**
     * @dataProvider processExpressionsEvaluatesClosureWithExtraParamsDataProvider
     */
    public function testEvaluateClosureWithExtraParamsWhenValuesUsedInThisExpressionAlreadyEvaluated(
        ClosureWithExtraParams $closureWithExtraParams,
        $expectedEvaluationResult
    ) {
        $values['expr_object'] = new ParsedExpression('true', new ConstantNode(true));
        $values['expr_closure'] = function () {
            return true;
        };
        $values['dependent_expr'] = '=true == expr_string';
        $values['expr_string'] = '=true';
        $values['scalar'] = 123;
        $values['attr']['data-scalar'] = 'foo';
        $values['attr']['data-expr'] = '=true';
        $values['attr']['data-from-context'] = '=context["css_class"]';

        $values['expr_closure_with_extra_params'] = $closureWithExtraParams;

        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertSame($expectedEvaluationResult, $values['expr_closure_with_extra_params']);
    }

    /**
     * @dataProvider processExpressionsEvaluatesClosureWithExtraParamsDataProvider
     */
    public function testEvaluateClosureWithExtraParamsWhenValuesUsedInThisExpressionIsEvaluatedYet(
        ClosureWithExtraParams $closureWithExtraParams,
        $expectedEvaluationResult
    ) {
        $values['expr_closure_with_extra_params'] = $closureWithExtraParams;

        $values['expr_object'] = new ParsedExpression('true', new ConstantNode(true));
        $values['expr_closure'] = function () {
            return true;
        };
        $values['dependent_expr'] = '=true == expr_string';
        $values['expr_string'] = '=true';
        $values['scalar'] = 123;
        $values['attr']['data-scalar'] = 'foo';
        $values['attr']['data-expr'] = '=true';
        $values['attr']['data-from-context'] = '=context["css_class"]';

        $context = new LayoutContext();
        $context->set('css_class', 'test_class');
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertSame($expectedEvaluationResult, $values['expr_closure_with_extra_params']);
    }

    public function processExpressionsEvaluatesClosureWithExtraParamsDataProvider(): array
    {
        return [
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val;
                    },
                    ['expr_object'],
                    'expr_object'
                ),
                true
            ],
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val;
                    },
                    ['expr_closure'],
                    'expr_closure'
                ),
                true
            ],
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val;
                    },
                    ['dependent_expr'],
                    'dependent_expr'
                ),
                true
            ],
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val;
                    },
                    ['expr_string'],
                    'expr_string'
                ),
                true
            ],
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val;
                    },
                    ['scalar'],
                    'scalar'
                ),
                123
            ],
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val['data-scalar'];
                    },
                    ['attr'],
                    'attr["data-scalar"]'
                ),
                'foo'
            ],
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val['data-expr'];
                    },
                    ['attr'],
                    'attr["data-expr"]'
                ),
                true
            ],
            [
                new ClosureWithExtraParams(
                    function ($context, $data, $val) {
                        return $val['data-from-context'];
                    },
                    ['attr'],
                    'attr["data-from-context"]'
                ),
                'test_class'
            ],
        ];
    }
}
