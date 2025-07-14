<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit;

use Oro\Component\ExpressionLanguage\Lexer;
use Oro\Component\ExpressionLanguage\Node as CustomNode;
use Oro\Component\ExpressionLanguage\Parser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Node;
use Symfony\Component\ExpressionLanguage\Node\ArgumentsNode;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\ConditionalNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\UnaryNode;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ParserTest extends TestCase
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
                new NameNode('a'),
                'a',
                ['a'],
            ],
            [
                new ConstantNode('a'),
                '"a"',
            ],
            [
                new ConstantNode(3),
                '3',
            ],
            [
                new ConstantNode(false),
                'false',
            ],
            [
                new ConstantNode(true),
                'true',
            ],
            [
                new ConstantNode(null),
                'null',
            ],
            [
                new UnaryNode('-', new ConstantNode(3)),
                '-3',
            ],
            [
                new CustomNode\BinaryNode('-', new ConstantNode(3), new ConstantNode(3)),
                '3 - 3',
            ],
            [
                new CustomNode\BinaryNode(
                    '*',
                    new CustomNode\BinaryNode('-', new ConstantNode(3), new ConstantNode(3)),
                    new ConstantNode(2)
                ),
                '(3 - 3) * 2',
            ],
            [
                new CustomNode\GetPropertyNode(
                    new NameNode('foo'),
                    new ConstantNode('bar', true),
                    new ArgumentsNode()
                ),
                'foo.bar',
                ['foo'],
            ],
            [
                new CustomNode\CollectionMethodAllNode(
                    new NameNode('foo'),
                    new ConstantNode('all', true),
                    $this->getArgumentsNode(new ConstantNode(true))
                ),
                'foo.all(true)',
                ['foo'],
            ],
            [
                new CustomNode\CollectionMethodAnyNode(
                    new NameNode('foo'),
                    new ConstantNode('any', true),
                    $this->getArgumentsNode(new ConstantNode(true))
                ),
                'foo.any(true)',
                ['foo'],
            ],
            [
                new CustomNode\CollectionMethodSumNode(
                    new NameNode('foo'),
                    new ConstantNode('sum', true),
                    $this->getArgumentsNode(new ConstantNode(true))
                ),
                'foo.sum(1)',
                ['foo'],
            ],
            [
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode(3),
                    new ArgumentsNode(),
                    GetAttrNode::ARRAY_CALL
                ),
                'foo[3]',
                ['foo'],
            ],
            [
                new ConditionalNode(
                    new ConstantNode(true),
                    new ConstantNode(true),
                    new ConstantNode(false)
                ),
                'true ? true : false',
            ],
            [
                new CustomNode\BinaryNode('matches', new ConstantNode('foo'), new ConstantNode('/foo/')),
                '"foo" matches "/foo/"',
            ],
            [
                new CustomNode\CollectionMethodAllNode(
                    new CustomNode\CollectionMethodAnyNode(
                        new NameNode('foo'),
                        new ConstantNode('any', true),
                        $this->getArgumentsNode(new ConstantNode(true))
                    ),
                    new ConstantNode('all', true),
                    $this->getArgumentsNode(new ConstantNode(true))
                ),
                'foo.any(true).all(true)',
                ['foo'],
            ],
            [
                new NameNode('foo'),
                'bar',
                ['foo' => 'bar'],
            ],
        ];
    }

    public function testParseNestedCondition(): void
    {
        $arrayNode = new ArrayNode();
        $arrayNode->addElement(new ConstantNode('bar'));

        $left = new CustomNode\BinaryNode(
            'in',
            new CustomNode\GetPropertyNode(
                new NameNode('item'),
                new ConstantNode('foo', true),
                new ArgumentsNode()
            ),
            $arrayNode
        );

        $allArguments = new ArgumentsNode();
        $allArguments->addElement(
            new CustomNode\BinaryNode(
                '>',
                new CustomNode\GetPropertyNode(
                    new NameNode('item'),
                    new ConstantNode('index', true),
                    new ArgumentsNode()
                ),
                new ConstantNode(10)
            )
        );

        $arguments = new CustomNode\BinaryNode(
            '>',
            new CustomNode\GetPropertyNode(
                new NameNode('value'),
                new ConstantNode('index', true),
                new ArgumentsNode()
            ),
            new ConstantNode(10)
        );

        $right = new CustomNode\CollectionMethodAllNode(
            new CustomNode\GetPropertyNode(
                new NameNode('item'),
                new ConstantNode('values', true),
                new ArgumentsNode()
            ),
            new ConstantNode('all', true),
            $this->getArgumentsNode($arguments)
        );

        $node = new CustomNode\CollectionMethodAnyNode(
            new NameNode('items'),
            new ConstantNode('any', true),
            $this->getArgumentsNode(new CustomNode\BinaryNode('and', $left, $right))
        );

        $expression = 'items.any(item.foo in ["bar"] and item.values.all(value.index > 10))';
        $names = ['items'];

        $lexer = new Lexer();
        $parser = new Parser([]);
        self::assertEquals($node, $parser->parse($lexer->tokenize($expression), $names));
    }

    private function getArgumentsNode(...$args): ArgumentsNode
    {
        $arguments = new ArgumentsNode();
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
