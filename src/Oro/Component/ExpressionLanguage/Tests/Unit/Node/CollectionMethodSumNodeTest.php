<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Oro\Component\ExpressionLanguage\Node\BinaryNode;
use Oro\Component\ExpressionLanguage\Node\CollectionMethodSumNode;
use Oro\Component\ExpressionLanguage\Node\GetPropertyNode;
use Oro\Component\ExpressionLanguage\Tests\Unit\Node\Stub\SimpleObject;
use Symfony\Component\ExpressionLanguage\Node\ArgumentsNode;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;

class CollectionMethodSumNodeTest extends AbstractNodeTest
{
    public function getEvaluateData(): array
    {
        $variables = [
            'items' => [
                [
                    'foo' => 'bar',
                    'index' => 11,
                ],
                [
                    'foo' => 'not_bar',
                    'index' => 200,
                ],
            ],
        ];

        $node = new CollectionMethodSumNode(
            new NameNode('items'),
            new ConstantNode('sum'),
            $this->getArgumentsNode(
                new GetPropertyNode(
                    new NameNode('item'),
                    new ConstantNode('index'),
                    new ArgumentsNode()
                )
            )
        );

        return [
            'sum int: items.sum(item.index)' => [
                'expected' => 211,
                'node' => $node,
                'variables' => $variables,
            ],
            'sum float: items.sum(item.index)' => [
                'expected' => 211.55,
                'node' => $node,
                'variables' => [
                    'items' => [
                        [
                            'foo' => 'bar',
                            'index' => 11.22,
                        ],
                        [
                            'foo' => 'not_bar',
                            'index' => 200.33,
                        ],
                    ],
                ],
            ],
            // Checks that "sample" is singularized to "sampleItem".
            'sample.sum(sampleItem.index)' => [
                'expected' => 211,
                'node' => new CollectionMethodSumNode(
                    new NameNode('sample'),
                    new ConstantNode('any'),
                    $this->getArgumentsNode(
                        new GetPropertyNode(
                            new NameNode('sampleItem'),
                            new ConstantNode('index'),
                            new ArgumentsNode()
                        )
                    )
                ),
                'variables' => [
                    'sample' => [
                        [
                            'foo' => 'bar',
                            'index' => 11,
                        ],
                        [
                            'foo' => 'not_bar',
                            'index' => 200,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getCompileData(): array
    {
        return [
            'simpleObject.sum(simpleObjectItem.index)' => [
                'expected' => 'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                    . '{ $$__name = $__value; } $__result = false; foreach ($simpleObject as $simpleObjectItem ) '
                    . '{ $__evaluated_result = $simpleObjectItem->index; $__result += $__evaluated_result; }'
                    . ' return $__result; }, get_defined_vars())',
                'node' => new CollectionMethodSumNode(
                    new NameNode('simpleObject'),
                    new ConstantNode('sum'),
                    $this->getArgumentsNode(
                        new GetPropertyNode(
                            new NameNode('simpleObjectItem'),
                            new ConstantNode('index'),
                            new ArgumentsNode()
                        )
                    )
                ),
                'functions' => [],
            ],
            'items.sum(item.index)' => [
                'expected' => 'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                    . '{ $$__name = $__value; } $__result = false; foreach ($items as $item ) '
                    . '{ $__evaluated_result = $item->index; $__result += $__evaluated_result; } '
                    . 'return $__result; }, get_defined_vars())',
                'node' => new CollectionMethodSumNode(
                    new NameNode('items'),
                    new ConstantNode('sum'),
                    $this->getArgumentsNode(
                        new GetPropertyNode(
                            new NameNode('item'),
                            new ConstantNode('index'),
                            new ArgumentsNode()
                        )
                    )
                ),
                'functions' => [],
            ],
        ];
    }

    private function getArgumentsNode(...$args): ArgumentsNode
    {
        $arguments = new ArgumentsNode();
        foreach ($args as $arg) {
            $arguments->addElement($arg);
        }

        return $arguments;
    }

    public function getDumpData(): array
    {
        return [
            'simpleObject.sum(simpleObjectItem)' => [
                'expected' => 'simpleObject.sum(simpleObjectItem)',
                'node' => new CollectionMethodSumNode(
                    new NameNode('simpleObject'),
                    new ConstantNode('sum', true),
                    $this->getArgumentsNode(new NameNode('simpleObjectItem'))
                ),
            ],
        ];
    }

    public function testConstructThrowsExceptionWhenNoArguments(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Method sum() should have exactly one argument');

        new CollectionMethodSumNode(
            new ArrayNode(),
            new ConstantNode('sum'),
            new ArrayNode()
        );
    }

    public function testEvaluateThrowsExceptionWhenNotTraversableVariable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to iterate on "items".');

        $variables = [
            'items' => new SimpleObject(),
        ];

        $arguments = new BinaryNode(
            '>',
            new GetPropertyNode(
                new NameNode('item'),
                new ConstantNode('index'),
                new ArgumentsNode()
            ),
            new ConstantNode(10)
        );
        $node = new CollectionMethodSumNode(
            new NameNode('items'),
            new ConstantNode('sum'),
            $this->getArgumentsNode($arguments)
        );

        // items.sum(item.index)
        $node->evaluate([], $variables);
    }

    public function testEvaluateThrowsExceptionWhenNoName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to get name of iterable "[]".');

        $variables = [
            'items' => new SimpleObject(),
        ];

        $arguments = new BinaryNode(
            '>',
            new GetPropertyNode(
                new NameNode('item'),
                new ConstantNode('index'),
                new ArgumentsNode()
            ),
            new ConstantNode(10)
        );
        $node = new CollectionMethodSumNode(
            new ArrayNode(),
            new ConstantNode('sum'),
            $this->getArgumentsNode($arguments)
        );

        $node->evaluate([], $variables);
    }

    public function testEvaluateThrowsExceptionWhenNotNumeric(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to sum a non-numeric value \'not_numeric\' in items.');

        $variables = [
            'items' => [['index' => 42], ['index' => 'not_numeric']],
        ];

        $node = new CollectionMethodSumNode(
            new NameNode('items'),
            new ConstantNode('sum'),
            $this->getArgumentsNode(
                new GetPropertyNode(
                    new NameNode('item'),
                    new ConstantNode('index'),
                    new ArgumentsNode()
                )
            )
        );

        $node->evaluate([], $variables);
    }
}
