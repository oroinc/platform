<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\UnaryNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Bundle\LayoutBundle\ExpressionLanguage\ExpressionManipulator;

class ExpressionManipulatorTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $expected = [
            'expression' => '!true === false',
            'node' => [
                'Symfony\Component\ExpressionLanguage\Node\BinaryNode' => [
                    'attributes' => [
                        'operator' => '==='
                    ],
                    'nodes' => [
                        'left' => [
                            'Symfony\Component\ExpressionLanguage\Node\UnaryNode' => [
                                'attributes' => ['operator' => '!'],
                                'nodes' => [
                                    'node' => [
                                        'Symfony\Component\ExpressionLanguage\Node\ConstantNode' => [
                                            'attributes' => ['value' => true],
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'right' => [
                            'Symfony\Component\ExpressionLanguage\Node\ConstantNode' => [
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

    /**
     * @return ParsedExpression
     */
    protected function createParsedExpression()
    {
        $node = new BinaryNode('===', new UnaryNode('!', new ConstantNode(true)), new ConstantNode(false));
        $parsedExpression = new ParsedExpression('!true === false', $node);

        return $parsedExpression;
    }
}
