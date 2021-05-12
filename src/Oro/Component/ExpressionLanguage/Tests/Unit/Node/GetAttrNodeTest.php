<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Oro\Component\ExpressionLanguage\Node\BinaryNode;
use Oro\Component\ExpressionLanguage\Node\GetAttrNode;
use Oro\Component\ExpressionLanguage\Tests\Unit\Node\Stub\SimpleObject;
use Symfony\Component\ExpressionLanguage\Node\ArgumentsNode;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;

class GetAttrNodeTest extends AbstractNodeTest
{
    /**
     * {@inheritDoc}
     */
    public function getEvaluateData(): array
    {
        return [
            [
                'b',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode(0),
                    new ArgumentsNode(),
                    GetAttrNode::ARRAY_CALL
                ),
                ['foo' => ['b' => 'a', 'b']]
            ],
            [
                'a',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('b'),
                    new ArgumentsNode(),
                    GetAttrNode::ARRAY_CALL
                ),
                ['foo' => ['b' => 'a', 'b']]
            ],

            [
                'bar',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('foo'),
                    new ArgumentsNode(),
                    GetAttrNode::PROPERTY_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                'a',
                new GetAttrNode(
                    new NameNode('foo'),
                    new NameNode('index'),
                    new ArgumentsNode(),
                    GetAttrNode::ARRAY_CALL
                ),
                ['foo' => ['b' => 'a', 'b'], 'index' => 'b']
            ],
        ];
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCompileData(): array
    {
        return [
            [
                '$foo[0]',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode(0),
                    new ArgumentsNode(),
                    GetAttrNode::ARRAY_CALL
                )
            ],
            [
                '$foo["b"]',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('b'),
                    new ArgumentsNode(),
                    GetAttrNode::ARRAY_CALL
                )
            ],

            [
                '$foo->foo',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('foo'),
                    new ArgumentsNode(),
                    GetAttrNode::PROPERTY_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                .'{ $$__name = $__value; } $__result = true; foreach ($foo as $fooItem ) '
                .'{ $__evaluated_result = ($fooItem->index > 10); if (!$__evaluated_result) { return false; } '
                .'$__result = $__result && $__evaluated_result; } return $__result; }, get_defined_vars())',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('all'),
                    new BinaryNode(
                        '>',
                        new GetAttrNode(
                            new NameNode('fooItem'),
                            new ConstantNode('index'),
                            new ArgumentsNode(),
                            GetAttrNode::PROPERTY_CALL
                        ),
                        new ConstantNode(10)
                    ),
                    GetAttrNode::ALL_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                .'{ $$__name = $__value; } $__result = false; foreach ($foo as $fooItem ) '
                .'{ $__evaluated_result = ($fooItem->index > 10); if ($__evaluated_result) { return true; } '
                .'$__result = $__result || $__evaluated_result; } return $__result; }, get_defined_vars())',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('all'),
                    new BinaryNode(
                        '>',
                        new GetAttrNode(
                            new NameNode('fooItem'),
                            new ConstantNode('index'),
                            new ArgumentsNode(),
                            GetAttrNode::PROPERTY_CALL
                        ),
                        new ConstantNode(10)
                    ),
                    GetAttrNode::ANY_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                .'{ $$__name = $__value; } $__result = false; foreach ($foo as $fooItem ) '
                .'{ $__evaluated_result = $fooItem->index; '
                .'$__result += $__evaluated_result; } return $__result; }, get_defined_vars())',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('all'),
                    new GetAttrNode(
                        new NameNode('fooItem'),
                        new ConstantNode('index'),
                        new ArgumentsNode(),
                        GetAttrNode::PROPERTY_CALL
                    ),
                    GetAttrNode::SUM_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                '$foo[$index]',
                new GetAttrNode(
                    new NameNode('foo'),
                    new NameNode('index'),
                    new ArgumentsNode(),
                    GetAttrNode::ARRAY_CALL
                )
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDumpData(): array
    {
        // Dumping is not supported yet
        return [];
    }

    /**
     * @dataProvider methodsEvaluateDataProvider
     */
    public function testMethodsEvaluate(array $variables, bool $expectedData)
    {
        $arrayNode = new ArrayNode();
        $arrayNode->addElement(new ConstantNode('bar'));

        $left = new BinaryNode(
            'in',
            new GetAttrNode(
                new NameNode('item'),
                new ConstantNode('foo'),
                new ArgumentsNode(),
                GetAttrNode::PROPERTY_CALL
            ),
            $arrayNode
        );

        $allsArguments = new ArgumentsNode();
        $allsArguments->addElement(new BinaryNode(
            '>',
            new GetAttrNode(
                new NameNode('item'),
                new ConstantNode('index'),
                new ArgumentsNode(),
                GetAttrNode::PROPERTY_CALL
            ),
            new ConstantNode(10)
        ));

        $arguments = new BinaryNode(
            '>',
            new GetAttrNode(
                new NameNode('value'),
                new ConstantNode('index'),
                new ArgumentsNode(),
                GetAttrNode::PROPERTY_CALL
            ),
            new ConstantNode(10)
        );

        $right = new GetAttrNode(
            new GetAttrNode(
                new NameNode('item'),
                new ConstantNode('values'),
                new ArgumentsNode(),
                GetAttrNode::PROPERTY_CALL
            ),
            new ConstantNode('all'),
            $arguments,
            GetAttrNode::ALL_CALL
        );

        $node = new GetAttrNode(
            new NameNode('items'),
            new ConstantNode('any'),
            new BinaryNode('and', $left, $right),
            GetAttrNode::ANY_CALL
        );
        // items.any(item.foo in ["bar"] and item.values.all(value.index > 10))
        $this->assertSame($expectedData, $node->evaluate([], $variables));
    }

    public function methodsEvaluateDataProvider(): array
    {
        return [
            'true' => [
                'variables' => [
                    'items' => [
                        [
                            'foo' => 'bar',
                            'values' => [['index' => 11]]
                        ],
                        [
                            'foo' => 'not_bar',
                            'values' => [['index' => 200]]
                        ]
                    ]
                ],
                'expectedValue' => true,
            ],
            'true with object' => [
                'variables' => [
                    'items' => [
                        new SimpleObject(),
                        [
                            'foo' => 'baz',
                            'values' => [['index' => 9]]
                        ]
                    ]
                ],
                'expectedValue' => true,
            ],
            'false' => [
                'variables' => [
                    'items' => [
                        [
                            'foo' => 'not_bar',
                            'values' => [['index' => 11]]
                        ],
                        [
                            'foo' => 'not_bar',
                            'values' => [['index' => 200]]
                        ]
                    ]
                ],
                'expectedValue' => false,
            ],
            'false nested' => [
                'variables' => [
                    'items' => [
                        [
                            'foo' => 'not_bar',
                            'values' => [['index' => 9], ['index' => 11]]
                        ],
                        [
                            'foo' => 'bar',
                            'values' => [['index' => 9]]
                        ]
                    ]
                ],
                'expectedValue' => false,
            ]
        ];
    }

    /**
     * @dataProvider sumMethodEvaluateDataProvider
     */
    public function testMethodSumEvaluate(array $variables, $expectedData)
    {
        $argument = new GetAttrNode(
            new NameNode('item'),
            new ConstantNode('foo'),
            new ArgumentsNode(),
            GetAttrNode::PROPERTY_CALL
        );

        $node = new GetAttrNode(
            new NameNode('items'),
            new ConstantNode('sum'),
            $argument,
            GetAttrNode::SUM_CALL
        );
        // items.sum(item.foo)
        $this->assertSame($expectedData, $node->evaluate([], $variables));
    }

    public function sumMethodEvaluateDataProvider(): array
    {
        return [
            'sum' => [
                'variables' => [
                    'items' => [
                        [
                            'foo' => 10,
                        ],
                        [
                            'foo' => 20,
                        ]
                    ]
                ],
                'expectedValue' => 30,
            ],
            'sum float' => [
                'variables' => [
                    'items' => [
                        [
                            'foo' => 3.4,
                        ],
                        [
                            'foo' => 20,
                        ]
                    ]
                ],
                'expectedValue' => 23.4,
            ],
        ];
    }

    public function testMethodSumNonNumericEvaluate()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to sum a non-numeric value, value: ''.");

        $variables = [
            'items' => [
                ['foo' => '']
            ]
        ];
        $argument = new GetAttrNode(
            new NameNode('item'),
            new ConstantNode('foo'),
            new ArgumentsNode(),
            GetAttrNode::PROPERTY_CALL
        );

        $node = new GetAttrNode(
            new NameNode('items'),
            new ConstantNode('sum'),
            $argument,
            GetAttrNode::SUM_CALL
        );
        // items.sum(item.foo)
        $node->evaluate([], $variables);
    }

    public function testUnknownNodeType()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable node type: -1. Available types are 1, 2, 3, 4, 5.');

        $node = new GetAttrNode(
            new NameNode('item'),
            new ConstantNode('foo'),
            new ArgumentsNode(),
            -1
        );

        $node->evaluate([], []);
    }

    public function testMethodEvaluateAllWithNotTraversableVariables()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to iterate through a non-object.');

        $variables = [
            'items' => new SimpleObject()
        ];

        $arguments = new BinaryNode(
            '>',
            new GetAttrNode(
                new NameNode('item'),
                new ConstantNode('index'),
                new ArgumentsNode(),
                GetAttrNode::PROPERTY_CALL
            ),
            new ConstantNode(10)
        );
        $node = new GetAttrNode(
            new NameNode('items'),
            new ConstantNode('all'),
            $arguments,
            GetAttrNode::ALL_CALL
        );

        // items.all(item.index > 10)
        $node->evaluate([], $variables);
    }

    public function testMethodEvaluateAnyWithNotTraversableVariables()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to iterate through a non-object.');

        $variables = [
            'items' => new SimpleObject()
        ];

        $arguments = new BinaryNode(
            '>',
            new GetAttrNode(
                new NameNode('item'),
                new ConstantNode('index'),
                new ArgumentsNode(),
                GetAttrNode::PROPERTY_CALL
            ),
            new ConstantNode(10)
        );
        $node = new GetAttrNode(
            new NameNode('items'),
            new ConstantNode('any'),
            $arguments,
            GetAttrNode::ANY_CALL
        );

        // items.any(item.index > 10)
        $node->evaluate([], $variables);
    }

    public function testMethodEvaluateSumWithNotTraversableVariables()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to iterate through a non-object.');

        $variables = [
            'items' => new SimpleObject()
        ];

        $argument = new GetAttrNode(
            new NameNode('item'),
            new ConstantNode('foo'),
            new ArgumentsNode(),
            GetAttrNode::PROPERTY_CALL
        );
        $node = new GetAttrNode(
            new NameNode('items'),
            new ConstantNode('sum'),
            $argument,
            GetAttrNode::SUM_CALL
        );

        // items.sum(item.foo)
        $node->evaluate([], $variables);
    }
}
