<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit;

use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExpressionLanguageTest extends \PHPUnit\Framework\TestCase
{
    private ExpressionLanguage $expressionLanguage;

    protected function setUp(): void
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function testCachedParse(): void
    {
        $savedParsedExpression = null;
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $expressionLanguage = new ExpressionLanguage($cache);

        $cache->expects(self::exactly(2))
            ->method('getItem')
            ->with('1%20%2B%201%2F%2F')
            ->willReturn($cacheItem);
        $cacheItem->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static function () use (&$savedParsedExpression) {
                return $savedParsedExpression;
            });
        $cacheItem->expects(self::once())
            ->method('set')
            ->with(self::isInstanceOf(ParsedExpression::class))
            ->willReturnCallback(static function ($expression) use (&$savedParsedExpression) {
                $savedParsedExpression = $expression;

                return true;
            });
        $cache->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $parsedExpression = $expressionLanguage->parse('1 + 1', []);
        self::assertSame($savedParsedExpression, $parsedExpression);

        $parsedExpression = $expressionLanguage->parse('1 + 1', []);
        self::assertSame($savedParsedExpression, $parsedExpression);
    }

    public function testConstantFunction(): void
    {
        $expression = 'constant("PHP_VERSION")';
        self::assertEquals(PHP_VERSION, $this->expressionLanguage->evaluate($expression));
        self::assertEquals('\\' . $expression, $this->expressionLanguage->compile($expression));
    }

    public function testProviders(): void
    {
        $this->expressionLanguage = new ExpressionLanguage(
            null,
            [
                new class() implements ExpressionFunctionProviderInterface {
                    public function getFunctions(): array
                    {
                        return [
                            new ExpressionFunction(
                                'identity',
                                static fn ($input) => $input,
                                static fn (array $values, $input) => $input
                            ),
                        ];
                    }
                },
            ]
        );
        self::assertEquals('foo', $this->expressionLanguage->evaluate('identity("foo")'));
        self::assertEquals('"foo"', $this->expressionLanguage->compile('identity("foo")'));
    }

    /**
     * @dataProvider shortCircuitProviderEvaluateDataProvider
     */
    public function testShortCircuitOperatorsEvaluate(string $expression, array $values, bool $expected): void
    {
        self::assertEquals($expected, $this->expressionLanguage->evaluate($expression, $values));
    }

    /**
     * @dataProvider shortCircuitProviderCompileDataProvider
     */
    public function testShortCircuitOperatorsCompile(string $expression, array $names, bool $expected): void
    {
        $result = null;
        eval(sprintf('$result = %s;', $this->expressionLanguage->compile($expression, $names)));
        self::assertSame($expected, $result);
    }

    public function shortCircuitProviderEvaluateDataProvider(): array
    {
        $object = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['foo'])
            ->getMock();
        $object->expects(self::never())
            ->method(self::anything());

        return [
            ['false and object.foo', ['object' => $object], false],
            ['false && object.foo', ['object' => $object], false],
            ['true || object.foo', ['object' => $object], true],
            ['true or object.foo', ['object' => $object], true],
        ];
    }

    public function shortCircuitProviderCompileDataProvider(): array
    {
        return [
            ['false and foo', ['foo' => 'foo'], false],
            ['false && foo', ['foo' => 'foo'], false],
            ['true || foo', ['foo' => 'foo'], true],
            ['true or foo', ['foo' => 'foo'], true],
        ];
    }

    public function testCachingForOverriddenVariableNames(): void
    {
        $expression = 'a + b';
        $this->expressionLanguage->evaluate($expression, ['a' => 1, 'b' => 1]);
        $result = $this->expressionLanguage->compile($expression, ['a', 'B' => 'b']);
        self::assertSame('($a + $B)', $result);
    }

    public function testCachingWithDifferentNamesOrder(): void
    {
        $savedParsedExpressions = [];
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $expressionLanguage = new ExpressionLanguage($cache);
        $key = 'a%20%2B%20b%2F%2Fa%7CB%3Ab';

        $cache->expects(self::exactly(2))
            ->method('getItem')
            ->with($key)
            ->willReturn($cacheItem);
        $cacheItem->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static function () use (&$savedParsedExpressions, $key) {
                return $savedParsedExpressions[$key] ?? null;
            });
        $cacheItem->expects(self::once())
            ->method('set')
            ->with(self::isInstanceOf(ParsedExpression::class))
            ->willReturnCallback(static function ($expression) use (&$savedParsedExpressions, $key) {
                $savedParsedExpressions[$key] = $expression;
            });
        $cache->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $expression = 'a + b';
        $expressionLanguage->compile($expression, ['a', 'B' => 'b']);
        $expressionLanguage->compile($expression, ['B' => 'b', 'a']);
    }

    /**
     * @dataProvider lintDataProvider
     *
     * @param string $expression
     */
    public function testLint(string $expression): void
    {
        $this->expectNotToPerformAssertions();

        (new ExpressionLanguage())->lint($expression, null);
    }

    /**
     * @dataProvider lintDataProvider
     *
     * @param string $expression
     */
    public function testLintWhenExpressionObject(string $expression): void
    {
        $this->expectNotToPerformAssertions();

        (new ExpressionLanguage())->lint(new Expression($expression), null);
    }

    public function lintDataProvider(): array
    {
        return [
            ['expression' => '1 + 1'],
            ['expression' => 'a + b'],

            ['expression' => 'a = b'],
            ['expression' => 'a != b'],
            ['expression' => 'a == b'],
            ['expression' => 'a in b'],
            ['expression' => 'a not in b'],

            ['expression' => 'item.property'],
            ['expression' => 'item["property"]'],

            ['expression' => 'items.all(item.value > 42)'],
            ['expression' => 'items.any(item.value < 42)'],
            ['expression' => 'items.sum(item.value)'],

            ['expression' => 'items.any(item.values.all(value.sum(valueItem.number[i]) > 42))'],
        ];
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param string $expression
     * @param array $values
     * @param mixed $expected
     */
    public function testEvaluate(string $expression, array $values, mixed $expected): void
    {
        $this->expectNotToPerformAssertions();

        self::assertSame($expected, (new ExpressionLanguage())->evaluate($expression, $values));
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param string $expression
     * @param array $values
     * @param mixed $expected
     */
    public function testEvaluateWhenExpressionObject(string $expression, array $values, mixed $expected): void
    {
        $this->expectNotToPerformAssertions();

        self::assertSame($expected, (new ExpressionLanguage())->evaluate(new Expression($expression), $values));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function evaluateDataProvider(): array
    {
        $values = [
            'a' => 42,
            'b' => 4242,
            'c' => '4242',
            'd' => [42, 4242],
            'e' => 42.42,
            'object' => (object)['property' => 42],
            'arrayObject' => new \ArrayObject(['key' => 4242]),
            'array' => ['key' => 4242],
            'items' => [['value' => 42], ['value' => 4242]],
            'complexItems' => [
                ['values' => [['number' => ['i' => 42]], ['number' => ['i' => 4242]]]],
            ],
        ];

        return [
            // Common cases.
            ['expression' => '1 + 1', [], 2],
            ['expression' => 'a + b', $values, $values['a'] + $values['b']],

            // {@see \Oro\Component\ExpressionLanguage\Node\BinaryNode} cases.
            ['expression' => 'a = b', $values, false],
            ['expression' => 'b = c', $values, false],

            ['expression' => 'a = 42', $values, true],
            ['expression' => 'a == 42', $values, true],

            ['expression' => 'a = "42"', $values, false],
            ['expression' => 'a == "42"', $values, true],

            ['expression' => 'b = "4242"', $values, false],
            ['expression' => 'b == "4242"', $values, true],

            ['expression' => 'a != b', $values, true],
            ['expression' => 'b != c', $values, true],
            ['expression' => 'a == b', $values, false],
            ['expression' => 'b == c', $values, true],

            ['expression' => 'e = 42.42', $values, true],
            ['expression' => 'e == 42.42', $values, true],
            ['expression' => 'e = "42.42"', $values, false],
            ['expression' => 'e == "42.42"', $values, true],

            ['expression' => 'a in d', $values, true],
            ['expression' => 'c in d', $values, false],
            ['expression' => 'a not in d', $values, false],
            ['expression' => 'c not in d', $values, true],

            // {@see \Oro\Component\ExpressionLanguage\Node\GetPropertyNode} cases.
            ['expression' => 'object.property', $values, $values['object']->property],
            ['expression' => 'array["key"]', $values, $values['array']['key']],

            // {@see \Oro\Component\ExpressionLanguage\Node\CollectionMethodAllNode} cases.
            ['expression' => 'items.all(item.value > 0)', $values, true],
            ['expression' => 'items.all(item.value > 42)', $values, false],

            // {@see \Oro\Component\ExpressionLanguage\Node\CollectionMethodAnyNode} cases.
            ['expression' => 'items.any(item.value > 0)', $values, true],
            ['expression' => 'items.any(item.value > 42)', $values, true],
            ['expression' => 'items.any(item.value > 4242)', $values, false],
            ['expression' => 'items.any(item.value = 42)', $values, true],

            // {@see \Oro\Component\ExpressionLanguage\Node\CollectionMethodSumNode} cases.
            [
                'expression' => 'items.sum(item.value)',
                $values,
                $values['items'][0]['value'] + $values['items'][1]['value'],
            ],

            // Combined cases.
            [
                'expression' =>
                    'complexItems.any('
                        . 'complexItem.values.all('
                            . 'value.sum(value.number["i"]) > 0'
                        . ') == true'
                    . ')',
                $values,
                true,
            ],
            [
                'expression' =>
                    'items.sum('
                        . 'item.value > 42 ? 2 : 1'
                    . ')',
                $values,
                3,
            ],
        ];
    }
}
