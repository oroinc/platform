<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit;

use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionLanguageTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionLanguage */
    private $expressionLanguage;

    protected function setUp(): void
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function testCachedParse()
    {
        $savedParsedExpression = null;
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $expressionLanguage = new ExpressionLanguage($cache);

        $cache->expects($this->exactly(2))
            ->method('getItem')
            ->with('1%20%2B%201%2F%2F')
            ->willReturn($cacheItem);
        $cacheItem->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function () use (&$savedParsedExpression) {
                return $savedParsedExpression;
            });
        $cacheItem->expects($this->once())
            ->method('set')
            ->with($this->isInstanceOf(ParsedExpression::class))
            ->willReturnCallback(function ($expression) use (&$savedParsedExpression) {
                $savedParsedExpression = $expression;

                return true;
            });
        $cache->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $parsedExpression = $expressionLanguage->parse('1 + 1', []);
        $this->assertSame($savedParsedExpression, $parsedExpression);

        $parsedExpression = $expressionLanguage->parse('1 + 1', []);
        $this->assertSame($savedParsedExpression, $parsedExpression);
    }

    public function testConstantFunction()
    {
        $expression = 'constant("PHP_VERSION")';
        $this->assertEquals(PHP_VERSION, $this->expressionLanguage->evaluate($expression));
        $this->assertEquals('\\'.$expression, $this->expressionLanguage->compile($expression));
    }

    public function testProviders()
    {
        $this->expressionLanguage = new ExpressionLanguage(
            null,
            [
                new class() implements ExpressionFunctionProviderInterface {
                    /**
                     * {@inheritdoc}
                     */
                    public function getFunctions(): array
                    {
                        return [
                            new ExpressionFunction(
                                'identity',
                                static function ($input) {
                                    return $input;
                                },
                                static function (array $values, $input) {
                                    return $input;
                                }
                            ),
                        ];
                    }
                }
            ]
        );
        $this->assertEquals('foo', $this->expressionLanguage->evaluate('identity("foo")'));
        $this->assertEquals('"foo"', $this->expressionLanguage->compile('identity("foo")'));
    }

    /**
     * @dataProvider shortCircuitProviderEvaluateDataProvider
     */
    public function testShortCircuitOperatorsEvaluate(string $expression, array $values, bool $expected)
    {
        $this->assertEquals($expected, $this->expressionLanguage->evaluate($expression, $values));
    }

    /**
     * @dataProvider shortCircuitProviderCompileDataProvider
     */
    public function testShortCircuitOperatorsCompile(string $expression, array $names, bool $expected)
    {
        $result = null;
        eval(sprintf('$result = %s;', $this->expressionLanguage->compile($expression, $names)));
        $this->assertSame($expected, $result);
    }

    public function shortCircuitProviderEvaluateDataProvider(): array
    {
        $object = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['foo'])
            ->getMock();
        $object->expects($this->never())
            ->method('foo');

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

    public function testCachingForOverriddenVariableNames()
    {
        $expression = 'a + b';
        $this->expressionLanguage->evaluate($expression, ['a' => 1, 'b' => 1]);
        $result = $this->expressionLanguage->compile($expression, ['a', 'B' => 'b']);
        $this->assertSame('($a + $B)', $result);
    }

    public function testCachingWithDifferentNamesOrder()
    {
        $savedParsedExpressions = [];
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $expressionLanguage = new ExpressionLanguage($cache);
        $key = 'a%20%2B%20b%2F%2Fa%7CB%3Ab';

        $cache->expects($this->exactly(2))
            ->method('getItem')
            ->with($key)
            ->willReturn($cacheItem);
        $cacheItem->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function () use (&$savedParsedExpressions, $key) {
                return $savedParsedExpressions[$key] ?? null;
            });
        $cacheItem->expects($this->once())
            ->method('set')
            ->with($this->isInstanceOf(ParsedExpression::class))
            ->willReturnCallback(function ($expression) use (&$savedParsedExpressions, $key) {
                $savedParsedExpressions[$key] = $expression;
            });
        $cache->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $expression = 'a + b';
        $expressionLanguage->compile($expression, ['a', 'B' => 'b']);
        $expressionLanguage->compile($expression, ['B' => 'b', 'a']);
    }
}
