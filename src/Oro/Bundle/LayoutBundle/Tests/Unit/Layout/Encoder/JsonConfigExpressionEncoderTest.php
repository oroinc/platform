<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Encoder;

use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\Action;

use Oro\Bundle\LayoutBundle\ExpressionLanguage\ExpressionManipulator;
use Oro\Bundle\LayoutBundle\Layout\Encoder\JsonConfigExpressionEncoder;

class JsonConfigExpressionEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeExpr()
    {
        $parsedExpression = new ParsedExpression('true', new ConstantNode(true));
        $expressionManipulator = $this->getMock(ExpressionManipulator::class);
        $expressionManipulator->expects($this->once())
            ->method('toArray')
            ->with($parsedExpression)
            ->willReturn(
                [
                    'expression' => 'true',
                    'node' => [
                        'Symfony\Component\ExpressionLanguage\Node\ConstantNode' => [
                            'attributes' => ['value' => false],
                        ]
                    ]
                ]
            );

        $encoder = new JsonConfigExpressionEncoder($expressionManipulator);
        $result = $encoder->encodeExpr($parsedExpression);
        $this->assertJsonStringEqualsJsonFile(__DIR__.'/expression.json', $result);
    }

    public function testEncodeActions()
    {
        $expressionManipulator = $this->createExpressionManipulator();
        $encoder = new JsonConfigExpressionEncoder($expressionManipulator);
        $result = $encoder->encodeActions(
            [
                new Action('add', ['val1']),
                new Action('remove', ['val2'])
            ]
        );
        $this->assertEquals(
            '{"@actions":[{"name":"add","args":["val1"]},{"name":"remove","args":["val2"]}]}',
            $result
        );
    }

    /**
     * @return ExpressionManipulator
     */
    protected function createExpressionManipulator()
    {
        $expressionManipulator = new ExpressionManipulator();

        return $expressionManipulator;
    }
}
