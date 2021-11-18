<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Oro\Component\ExpressionLanguage\Node\BinaryNode;
use Oro\Component\ExpressionLanguage\Node\CollectionMethodAnyNode;
use Oro\Component\ExpressionLanguage\Node\GetPropertyNode;
use Oro\Component\ExpressionLanguage\Tests\Unit\Node\Stub\SimpleObject;
use Symfony\Component\ExpressionLanguage\Node\ArgumentsNode;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;

class CollectionMethodAnyNodeTest extends AbstractNodeTest
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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

        return [
            'items.any(item.index > 0)' => [
                'expected' => true,
                'node' => new CollectionMethodAnyNode(
                    new NameNode('items'),
                    new ConstantNode('any'),
                    $this->getArgumentsNode(
                        new BinaryNode(
                            '>',
                            new GetPropertyNode(
                                new NameNode('item'),
                                new ConstantNode('index'),
                                new ArgumentsNode()
                            ),
                            new ConstantNode(0)
                        )
                    )
                ),
                'variables' => $variables,
            ],
            'items.any(item.index > 11)' => [
                'expected' => true,
                'node' => new CollectionMethodAnyNode(
                    new NameNode('items'),
                    new ConstantNode('any'),
                    $this->getArgumentsNode(
                        new BinaryNode(
                            '>',
                            new GetPropertyNode(
                                new NameNode('item'),
                                new ConstantNode('index'),
                                new ArgumentsNode()
                            ),
                            new ConstantNode(11)
                        )
                    )
                ),
                'variables' => $variables,
            ],
            'items.any(item.index > 200)' => [
                'expected' => false,
                'node' => new CollectionMethodAnyNode(
                    new NameNode('items'),
                    new ConstantNode('any'),
                    $this->getArgumentsNode(
                        new BinaryNode(
                            '>',
                            new GetPropertyNode(
                                new NameNode('item'),
                                new ConstantNode('index'),
                                new ArgumentsNode()
                            ),
                            new ConstantNode(200)
                        )
                    )
                ),
                'variables' => $variables,
            ],
            // Checks that "sample" is singularized to "sampleItem".
            'sample.any(sampleItem.index > 200)' => [
                'expected' => false,
                'node' => new CollectionMethodAnyNode(
                    new NameNode('sample'),
                    new ConstantNode('any'),
                    $this->getArgumentsNode(
                        new BinaryNode(
                            '>',
                            new GetPropertyNode(
                                new NameNode('sampleItem'),
                                new ConstantNode('index'),
                                new ArgumentsNode()
                            ),
                            new ConstantNode(200)
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
            'simpleObject.any(simpleObjectItem.index > 10)' => [
                'expected' => 'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                    . '{ $$__name = $__value; } $__result = false; foreach ($simpleObject as $simpleObjectItem ) '
                    . '{ $__evaluated_result = ($simpleObjectItem->index > 10); if ($__evaluated_result) { '
                    . 'return true; } $__result = $__result || $__evaluated_result; } return $__result; }, '
                    . 'get_defined_vars())',
                'node' => new CollectionMethodAnyNode(
                    new NameNode('simpleObject'),
                    new ConstantNode('any'),
                    $this->getArgumentsNode(
                        new BinaryNode(
                            '>',
                            new GetPropertyNode(
                                new NameNode('simpleObjectItem'),
                                new ConstantNode('index'),
                                new ArgumentsNode()
                            ),
                            new ConstantNode(10),
                        )
                    )
                ),
                'functions' => [],
            ],
            'items.any(item.index > 10)' => [
                'expected' => 'call_user_func(function ($__variables) { foreach ($__variables as $__name => $__value) '
                    . '{ $$__name = $__value; } $__result = false; foreach ($items as $item ) '
                    . '{ $__evaluated_result = ($item->index > 10); if ($__evaluated_result) { return true; } '
                    . '$__result = $__result || $__evaluated_result; } return $__result; }, get_defined_vars())',
                'node' => new CollectionMethodAnyNode(
                    new NameNode('items'),
                    new ConstantNode('any'),
                    $this->getArgumentsNode(
                        new BinaryNode(
                            '>',
                            new GetPropertyNode(
                                new NameNode('item'),
                                new ConstantNode('index'),
                                new ArgumentsNode()
                            ),
                            new ConstantNode(10),
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
            'simpleObject.any(simpleObjectItem)' => [
                'expected' => 'simpleObject.any(simpleObjectItem)',
                'node' => new CollectionMethodAnyNode(
                    new NameNode('simpleObject'),
                    new ConstantNode('any', true),
                    $this->getArgumentsNode(new NameNode('simpleObjectItem'))
                ),
            ],
        ];
    }

    public function testConstructThrowsExceptionWhenNoArguments(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Method any() should have exactly one argument');

        new CollectionMethodAnyNode(
            new ArrayNode(),
            new ConstantNode('any'),
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
        $node = new CollectionMethodAnyNode(
            new NameNode('items'),
            new ConstantNode('any'),
            $this->getArgumentsNode($arguments)
        );

        // items.any(item.index > 10)
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
        $node = new CollectionMethodAnyNode(
            new ArrayNode(),
            new ConstantNode('any'),
            $this->getArgumentsNode($arguments)
        );

        $node->evaluate([], $variables);
    }
}
