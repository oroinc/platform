<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Layout\CacheExpressionProcessor;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\ExpressionLanguage\ClosureWithExtraParams;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\Tests\Unit\ExpressionLanguage\ExpressionProcessorTest;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class CacheExpressionProcessorTest extends ExpressionProcessorTest
{
    protected function createExpressionProcessor(): ExpressionProcessor
    {
        return new CacheExpressionProcessor(
            $this->expressionLanguage,
            $this->createEncoderRegistry()
        );
    }

    public function testProcessExpressionsDoNothingIfEvaluationOfExpressionsDisabledAndEncodingIsNotSetAndCachedTrue()
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
        $values['visible'] = '=true';
        $values['_cached'] = true;

        $initialVars = $values;

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, false, null);

        $this->assertSame($initialVars, $values);
    }

    public function testProcessExpressionsWithCachedTrue()
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
        $values['visible'] = '=true';
        $values['_cached'] = true;

        $context = new LayoutContext();
        $data = $this->createMock(DataAccessorInterface::class);
        $this->processor->processExpressions($values, $context, $data, true, null);

        $this->assertTrue($values['visible'], 'Failed asserting that an expression is evaluated');

        $this->assertNull($values['expr_object']);
        $this->assertNull($values['expr_closure']);
        $this->assertNull($values['expr_closure_with_extra_params']);
        $this->assertNull($values['expr_string']);
        $this->assertSame(123, $values['scalar']);
        $this->assertNull($values['attr']['enabled']);
        $this->assertSame('foo', $values['attr']['data-scalar']);
        $this->assertSame(['item1' => 'val1', 'item2' => null], $values['array_with_expr']);
    }
}
