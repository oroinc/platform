<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage\Encoder;

use Oro\Component\Layout\Action;
use Oro\Component\Layout\ExpressionLanguage\Encoder\JsonExpressionEncoder;
use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class JsonExpressionEncoderTest extends \PHPUnit\Framework\TestCase
{
    public function testEncodeExpr()
    {
        $parsedExpression = new ParsedExpression('true', new ConstantNode(true));
        $expressionManipulator = $this->createMock(ExpressionManipulator::class);
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

        $encoder = new JsonExpressionEncoder($expressionManipulator);
        $result = $encoder->encodeExpr($parsedExpression);
        $this->assertJsonStringEqualsJsonFile(__DIR__.'/Fixtures/expression.json', $result);
    }

    public function testEncodeActions()
    {
        $expressionManipulator = $this->createExpressionManipulator();
        $encoder = new JsonExpressionEncoder($expressionManipulator);
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
