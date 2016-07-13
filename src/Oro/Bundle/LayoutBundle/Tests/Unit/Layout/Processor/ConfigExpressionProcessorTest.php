<?php

namespace LayoutBundle\Tests\Unit\Layout\Processor;

use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\ArgumentsNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\OptionValueBag;

use Oro\Bundle\LayoutBundle\Layout\Processor\ConfigExpressionProcessor;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class ConfigExpressionProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionLanguage|\PHPUnit_Framework_MockObject_MockObject */
    protected $expressionLanguage;

    /** @var JsonConfigExpressionEncoder|\PHPUnit_Framework_MockObject_MockObject */
    protected $encoder;

    /** @var ConfigExpressionProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->expressionLanguage = $this->getMock(ExpressionLanguage::class);

        $encoderRegistry = $this
            ->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Encoder\ConfigExpressionEncoderRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->encoder = $this->getMockBuilder(JsonConfigExpressionEncoder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $encoderRegistry->expects($this->any())
            ->method('getEncoder')
            ->with('json')
            ->will($this->returnValue($this->encoder));

        $this->processor = new ConfigExpressionProcessor(
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
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['data-scalar'] = 'foo';
        $values['attr']['data-expr'] = '=true';
        $values['attr']['class'] = $classAttr;
        $values['label_attr']['enabled'] = '=true';
        $values['array_with_expr'] = ['item1' => 'val1', 'item2' => '=true'];

        $classExpr = new ParsedExpression(
            'context["css_class"]',
            new GetAttrNode(
                new NameNode('context'),
                new ConstantNode('css_class'),
                new ArgumentsNode(),
                GetAttrNode::ARRAY_CALL
            )
        );
        $this->expressionLanguage->expects($this->exactly(7))
            ->method('evaluate')
            ->will(
                $this->returnValueMap(
                    [
                        [$trueExpr, ['context' => $context, 'data' => $data], true],
                        [$classExpr, ['context' => $context, 'data' => $data], 'test_class']
                    ]
                )
            );
        $this->expressionLanguage->expects($this->exactly(6))
            ->method('parse')
            ->will(
                $this->returnValueMap(
                    [
                        ['true', ['context', 'data'], $trueExpr],
                        ['context["css_class"]', ['context', 'data'], $classExpr]
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

    public function testProcessExpressionsDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSet()
    {
        $context = new LayoutContext();
        $data = $this->getMock('Oro\Component\Layout\DataAccessorInterface');

        $expr = $this->getMockBuilder(ParsedExpression::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->expressionLanguage->expects($this->never())
            ->method('evaluate');

        $values['expr_object'] = $expr;
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['label_attr']['enabled'] = '=true';

        $this->expressionLanguage->expects($this->never())
            ->method('parse');

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
        $trueExprJson = '{encoded_expression_stub: "true"}';

        $classExpr = new ParsedExpression(
            'context["css_class"]',
            new GetAttrNode(
                new NameNode('context'),
                new ConstantNode('css_class'),
                new ArgumentsNode(),
                GetAttrNode::ARRAY_CALL
            )
        );
        $classExprJson = '{encoded_expression_stub: "class"}';

        $classAttr = new OptionValueBag();
        $classAttr->add('=context["css_class"]');
        $expectedClassAttr = new OptionValueBag();
        $expectedClassAttr->add($classExprJson);

        $values['expr_object'] = $trueExpr;
        $values['expr_string'] = '=true';
        $values['not_expr_string'] = '\=true';
        $values['scalar'] = 123;
        $values['attr']['enabled'] = '=true';
        $values['attr']['class'] = $classAttr;
        $values['label_attr']['enabled'] = '=true';

        $this->expressionLanguage->expects($this->exactly(4))
            ->method('parse')
            ->will(
                $this->returnValueMap(
                    [
                        ['true', ['context', 'data'], $trueExpr],
                        ['context["css_class"]', ['context', 'data'], $classExpr]
                    ]
                )
            );

        $this->encoder->expects($this->exactly(5))
            ->method('encodeExpr')
            ->will(
                $this->returnValueMap(
                    [
                        [$trueExpr, $trueExprJson],
                        [$classExpr, $classExprJson]
                    ]
                )
            );

        $this->processor->processExpressions($values, $context, $data, false, 'json');

        $this->assertSame(
            $trueExprJson,
            $values['expr_object'],
            'Failed asserting that an expression is encoded'
        );
        $this->assertSame(
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
        $this->assertSame(
            $trueExprJson,
            $values['attr']['enabled'],
            'Failed asserting that an expression in "attr" is parsed and encoded'
        );
        $this->assertEquals(
            $expectedClassAttr,
            $values['attr']['class'],
            'Failed asserting that "attr.class" is parsed and encoded'
        );
        $this->assertSame(
            $trueExprJson,
            $values['label_attr']['enabled'],
            'Failed asserting that an expression in "label_attr" is parsed and encoded'
        );
    }
}
