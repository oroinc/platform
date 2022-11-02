<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Oro\Component\ExpressionLanguage\Node\GetPropertyNode;
use Oro\Component\ExpressionLanguage\Tests\Unit\Node\Stub\SimpleObject;
use Symfony\Component\ExpressionLanguage\Node\ArgumentsNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;

class GetPropertyNodeTest extends AbstractNodeTest
{
    public function getEvaluateData(): array
    {
        return [
            'simpleObject.foo' => [
                'expected' => 'bar',
                'node' => new GetPropertyNode(
                    new NameNode('simpleObject'),
                    new ConstantNode('foo', true),
                    new ArgumentsNode()
                ),
                'variable' => ['simpleObject' => new SimpleObject()],
            ],
            'arrayObject.foo' => [
                'expected' => 'bar',
                'node' => new GetPropertyNode(
                    new NameNode('arrayObject'),
                    new ConstantNode('foo', true),
                    new ArgumentsNode()
                ),
                'variable' => ['arrayObject' => new \ArrayObject(['foo' => 'bar'])],
            ],
            'array.foo' => [
                'expected' => 'bar',
                'node' => new GetPropertyNode(
                    new NameNode('array'),
                    new ConstantNode('foo', true),
                    new ArgumentsNode()
                ),
                'variable' => ['array' => ['foo' => 'bar']],
            ],
        ];
    }

    public function getCompileData(): array
    {
        return [
            'simpleObject.foo' => [
                'expected' => '$simpleObject->foo',
                'node' => new GetPropertyNode(
                    new NameNode('simpleObject'),
                    new ConstantNode('foo', true),
                    new ArgumentsNode()
                ),
                'functions' => [],
            ],
        ];
    }

    public function getDumpData(): array
    {
        return [
            'simpleObject.foo' => [
                'expected' => 'simpleObject.foo',
                'node' => new GetPropertyNode(
                    new NameNode('simpleObject'),
                    new ConstantNode('foo', true),
                    new ArgumentsNode()
                ),
            ],
        ];
    }
}
