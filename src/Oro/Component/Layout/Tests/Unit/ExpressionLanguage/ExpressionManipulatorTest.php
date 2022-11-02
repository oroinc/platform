<?php

namespace Oro\Component\Layout\Tests\Unit\ExpressionLanguage;

use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\UnaryNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionManipulatorTest extends \PHPUnit\Framework\TestCase
{
    public function testToArray()
    {
        $expected = [
            'expression' => '!true === false',
            'node' => [
                BinaryNode::class => [
                    'attributes' => [
                        'operator' => '==='
                    ],
                    'nodes' => [
                        'left' => [
                            UnaryNode::class => [
                                'attributes' => ['operator' => '!'],
                                'nodes' => [
                                    'node' => [
                                        ConstantNode::class => [
                                            'attributes' => ['value' => true],
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'right' => [
                            ConstantNode::class => [
                                'attributes' => ['value' => false],
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $expressionManipulator = new ExpressionManipulator();

        $expression = $this->createParsedExpression();

        $actual = $expressionManipulator->toArray($expression);
        $this->assertEquals($expected, $actual);
    }

    private function createParsedExpression(): ParsedExpression
    {
        $node = new BinaryNode('===', new UnaryNode('!', new ConstantNode(true)), new ConstantNode(false));

        return new ParsedExpression('!true === false', $node);
    }
}
