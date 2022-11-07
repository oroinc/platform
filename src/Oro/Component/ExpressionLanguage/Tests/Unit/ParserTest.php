<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit;

use Oro\Component\ExpressionLanguage\Lexer;
use Oro\Component\ExpressionLanguage\Node as CustomNode;
use Oro\Component\ExpressionLanguage\Parser;
use Symfony\Component\ExpressionLanguage\Node;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    public function testParseWhenMethodNotSupported(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Unsupported method: bar(), supported methods are all(), any(), sum()'
            . ' around position 5 for expression `foo.bar(1)`.'
        );

        $lexer = new Lexer();
        $parser = new Parser([]);
        $parser->parse($lexer->tokenize('foo.bar(1)'));
    }

    public function testParseWhenArrayAccessAfterMethodCall(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Array calls on a method call is not allowed around position 11 for expression `foo.any(1)[3]`.'
        );

        $lexer = new Lexer();
        $parser = new Parser([]);
        $parser->parse($lexer->tokenize('foo.any(1)[3]'));
    }

    /**
     * @dataProvider parseOneArgumentRequiredDataProvider
     */
    public function testParseOneArgumentRequired(string $expression): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            sprintf('Method any() should have exactly one argument around position 5 for expression `%s`.', $expression)
        );

        $lexer = new Lexer();
        $parser = new Parser([]);
        $parser->parse($lexer->tokenize($expression));
    }

    public function parseOneArgumentRequiredDataProvider(): array
    {
        return [
            ['foo.any()'],
            ['foo.any(2, 4)'],
        ];
    }

    /**
     * @dataProvider getParseData
     */
    public function testParse(Node\Node $expectedNode, string $expression, array $names = []): void
    {
        $lexer = new Lexer();
        $parser = new Parser([]);
        self::assertEquals($expectedNode, $parser->parse($lexer->tokenize($expression), $names));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getParseData(): array
    {
        return [
            [
                new Node\NameNode('a'),
                'a',
                ['a'],
            ],
            [
                new Node\ConstantNode('a'),
                '"a"',
            ],
            [
                new Node\ConstantNode(3),
                '3',
            ],
            [
                new Node\ConstantNode(false),
                'false',
            ],
            [
                new Node\ConstantNode(true),
                'true',
            ],
            [
                new Node\ConstantNode(null),
                'null',
            ],
            [
                new Node\UnaryNode('-', new Node\ConstantNode(3)),
                '-3',
            ],
            [
                new CustomNode\BinaryNode('-', new Node\ConstantNode(3), new Node\ConstantNode(3)),
                '3 - 3',
            ],
            [
                new CustomNode\BinaryNode(
                    '*',
                    new CustomNode\BinaryNode('-', new Node\ConstantNode(3), new Node\ConstantNode(3)),
                    new Node\ConstantNode(2)
                ),
                '(3 - 3) * 2',
            ],
            [
                new CustomNode\GetPropertyNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('bar', true),
                    new Node\ArgumentsNode()
                ),
                'foo.bar',
                ['foo'],
            ],
            [
                new CustomNode\CollectionMethodAllNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('all', true),
                    $this->getArgumentsNode(new Node\ConstantNode(true))
                ),
                'foo.all(true)',
                ['foo'],
            ],
            [
                new CustomNode\CollectionMethodAnyNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('any', true),
                    $this->getArgumentsNode(new Node\ConstantNode(true))
                ),
                'foo.any(true)',
                ['foo'],
            ],
            [
                new CustomNode\CollectionMethodSumNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('sum', true),
                    $this->getArgumentsNode(new Node\ConstantNode(true))
                ),
                'foo.sum(1)',
                ['foo'],
            ],
            [
                new Node\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode(3),
                    new Node\ArgumentsNode(),
                    Node\GetAttrNode::ARRAY_CALL
                ),
                'foo[3]',
                ['foo'],
            ],
            [
                new Node\ConditionalNode(
                    new Node\ConstantNode(true),
                    new Node\ConstantNode(true),
                    new Node\ConstantNode(false)
                ),
                'true ? true : false',
            ],
            [
                new CustomNode\BinaryNode('matches', new Node\ConstantNode('foo'), new Node\ConstantNode('/foo/')),
                '"foo" matches "/foo/"',
            ],
            [
                new CustomNode\CollectionMethodAllNode(
                    new CustomNode\CollectionMethodAnyNode(
                        new Node\NameNode('foo'),
                        new Node\ConstantNode('any', true),
                        $this->getArgumentsNode(new Node\ConstantNode(true))
                    ),
                    new Node\ConstantNode('all', true),
                    $this->getArgumentsNode(new Node\ConstantNode(true))
                ),
                'foo.any(true).all(true)',
                ['foo'],
            ],
            [
                new Node\NameNode('foo'),
                'bar',
                ['foo' => 'bar'],
            ],
        ];
    }

    public function testParseNestedCondition(): void
    {
        $arrayNode = new Node\ArrayNode();
        $arrayNode->addElement(new Node\ConstantNode('bar'));

        $left = new CustomNode\BinaryNode(
            'in',
            new CustomNode\GetPropertyNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('foo', true),
                new Node\ArgumentsNode()
            ),
            $arrayNode
        );

        $allArguments = new Node\ArgumentsNode();
        $allArguments->addElement(
            new CustomNode\BinaryNode(
                '>',
                new CustomNode\GetPropertyNode(
                    new Node\NameNode('item'),
                    new Node\ConstantNode('index', true),
                    new Node\ArgumentsNode()
                ),
                new Node\ConstantNode(10)
            )
        );

        $arguments = new CustomNode\BinaryNode(
            '>',
            new CustomNode\GetPropertyNode(
                new Node\NameNode('value'),
                new Node\ConstantNode('index', true),
                new Node\ArgumentsNode()
            ),
            new Node\ConstantNode(10)
        );

        $right = new CustomNode\CollectionMethodAllNode(
            new CustomNode\GetPropertyNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('values', true),
                new Node\ArgumentsNode()
            ),
            new Node\ConstantNode('all', true),
            $this->getArgumentsNode($arguments)
        );

        $node = new CustomNode\CollectionMethodAnyNode(
            new Node\NameNode('items'),
            new Node\ConstantNode('any', true),
            $this->getArgumentsNode(new CustomNode\BinaryNode('and', $left, $right))
        );

        $expression = 'items.any(item.foo in ["bar"] and item.values.all(value.index > 10))';
        $names = ['items'];

        $lexer = new Lexer();
        $parser = new Parser([]);
        self::assertEquals($node, $parser->parse($lexer->tokenize($expression), $names));
    }

    private function getArgumentsNode(...$args): Node\ArgumentsNode
    {
        $arguments = new Node\ArgumentsNode();
        foreach ($args as $arg) {
            $arguments->addElement($arg);
        }

        return $arguments;
    }

    /**
     * @dataProvider getInvalidPostfixData
     */
    public function testParseWithInvalidPostfixData(string $expr, array $names = []): void
    {
        $this->expectException(SyntaxError::class);
        $lexer = new Lexer();
        $parser = new Parser([]);
        $parser->parse($lexer->tokenize($expr), $names);
    }

    public function getInvalidPostfixData(): array
    {
        return [
            [
                'foo."#"',
                ['foo'],
            ],
            [
                'foo."bar"',
                ['foo'],
            ],
            [
                'foo.**',
                ['foo'],
            ],
            [
                'foo.123',
                ['foo'],
            ],
        ];
    }
}
