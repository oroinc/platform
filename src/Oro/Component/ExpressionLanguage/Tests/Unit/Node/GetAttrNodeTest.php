<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Oro\Component\ExpressionLanguage\Node as CustomNode;
use Oro\Component\ExpressionLanguage\Tests\Unit\Node\Stub\SimpleObject;
use Symfony\Component\ExpressionLanguage\Node;
use Symfony\Component\ExpressionLanguage\Tests\Node\AbstractNodeTest;

class GetAttrNodeTest extends AbstractNodeTest
{
    /**
     * @return array
     */
    public function getEvaluateData()
    {
        return [
            [
                'b',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode(0),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                ),
                ['foo' => ['b' => 'a', 'b']]
            ],
            [
                'a',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('b'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                ),
                ['foo' => ['b' => 'a', 'b']]
            ],

            [
                'bar',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('foo'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::PROPERTY_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                'a',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\NameNode('index'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                ),
                ['foo' => ['b' => 'a', 'b'], 'index' => 'b']
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCompileData()
    {
        return [
            [
                '$foo[0]',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode(0),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                )
            ],
            [
                '$foo["b"]',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('b'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                )
            ],

            [
                '$foo->foo',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('foo'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::PROPERTY_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                .'{ $$__name = $__value; } $__result = true; foreach ($foo as $fooItem ) '
                .'{ $__evaluated_result = ($fooItem->index > 10); if (!$__evaluated_result) { return false; } '
                .'$__result = $__result && $__evaluated_result; } return $__result; }, get_defined_vars())',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('all'),
                    new CustomNode\BinaryNode(
                        '>',
                        new CustomNode\GetAttrNode(
                            new Node\NameNode('fooItem'),
                            new Node\ConstantNode('index'),
                            new Node\ArgumentsNode(),
                            CustomNode\GetAttrNode::PROPERTY_CALL
                        ),
                        new Node\ConstantNode(10)
                    ),
                    CustomNode\GetAttrNode::ALL_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                .'{ $$__name = $__value; } $__result = false; foreach ($foo as $fooItem ) '
                .'{ $__evaluated_result = ($fooItem->index > 10); if ($__evaluated_result) { return true; } '
                .'$__result = $__result || $__evaluated_result; } return $__result; }, get_defined_vars())',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('all'),
                    new CustomNode\BinaryNode(
                        '>',
                        new CustomNode\GetAttrNode(
                            new Node\NameNode('fooItem'),
                            new Node\ConstantNode('index'),
                            new Node\ArgumentsNode(),
                            CustomNode\GetAttrNode::PROPERTY_CALL
                        ),
                        new Node\ConstantNode(10)
                    ),
                    CustomNode\GetAttrNode::ANY_CALL
                ),
                ['foo' => new SimpleObject()]
            ],
            [
                '$foo[$index]',
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\NameNode('index'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                )
            ],
        ];
    }

    /**
     * @dataProvider methodsEvaluateDataProvider
     *
     * @param array $variables
     * @param bool $expectedData
     */
    public function testMethodsEvaluate(array $variables, $expectedData)
    {
        $arrayNode = new Node\ArrayNode();
        $arrayNode->addElement(new Node\ConstantNode('bar'));

        $left = new CustomNode\BinaryNode(
            'in',
            new CustomNode\GetAttrNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('foo'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            $arrayNode
        );

        $allsArguments = new Node\ArgumentsNode();
        $allsArguments->addElement(new CustomNode\BinaryNode(
            '>',
            new CustomNode\GetAttrNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('index'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            new Node\ConstantNode(10)
        ));

        $arguments = new CustomNode\BinaryNode(
            '>',
            new CustomNode\GetAttrNode(
                new Node\NameNode('value'),
                new Node\ConstantNode('index'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            new Node\ConstantNode(10)
        );

        $right = new CustomNode\GetAttrNode(
            new CustomNode\GetAttrNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('values'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            new Node\ConstantNode('all'),
            $arguments,
            CustomNode\GetAttrNode::ALL_CALL
        );

        $node = new CustomNode\GetAttrNode(
            new Node\NameNode('items'),
            new Node\ConstantNode('any'),
            new CustomNode\BinaryNode('and', $left, $right),
            CustomNode\GetAttrNode::ANY_CALL
        );
        // items.any(item.foo in ["bar"] and item.values.all(value.index > 10))
        $this->assertSame($expectedData, $node->evaluate([], $variables));
    }

    /**
     * @return array
     */
    public function methodsEvaluateDataProvider()
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
}
