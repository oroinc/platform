<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit;

use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\Tests\Fixtures\TestProvider;

class ExpressionLanguageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    public function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function testCachedParse()
    {
        $savedParsedExpression = null;
        $cacheMock = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $expressionLanguage = new ExpressionLanguage($cacheMock);

        $cacheMock
            ->expects($this->exactly(2))
            ->method('getItem')
            ->with('1%20%2B%201%2F%2F')
            ->willReturn($cacheItemMock);
        $cacheItemMock
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnCallback(function () use (&$savedParsedExpression) {
                return $savedParsedExpression;
            }));
        $cacheItemMock
            ->expects($this->exactly(1))
            ->method('set')
            ->with($this->isInstanceOf(ParsedExpression::class))
            ->will($this->returnCallback(function ($expression) use (&$savedParsedExpression) {
                $savedParsedExpression = $expression;

                return true;
            }));
        $cacheMock
            ->expects($this->exactly(1))
            ->method('save')
            ->with($cacheItemMock);

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
        $this->expressionLanguage = new ExpressionLanguage(null, [new TestProvider()]);
        $this->assertEquals('foo', $this->expressionLanguage->evaluate('identity("foo")'));
        $this->assertEquals('"foo"', $this->expressionLanguage->compile('identity("foo")'));
    }

    /**
     * @dataProvider shortCircuitProviderEvaluateDataProvider
     *
     * @param string $expression
     * @param array $values
     * @param bool $expected
     */
    public function testShortCircuitOperatorsEvaluate($expression, array $values, $expected)
    {
        $this->assertEquals($expected, $this->expressionLanguage->evaluate($expression, $values));
    }

    /**
     * @dataProvider shortCircuitProviderCompileDataProvider
     *
     * @param string $expression
     * @param array $names
     * @param bool $expected
     */
    public function testShortCircuitOperatorsCompile($expression, array $names, $expected)
    {
        $result = null;
        eval(sprintf('$result = %s;', $this->expressionLanguage->compile($expression, $names)));
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function shortCircuitProviderEvaluateDataProvider()
    {
        $object = $this->getMockBuilder('stdClass')->setMethods(['foo'])->getMock();
        $object->expects($this->never())->method('foo');

        return [
            ['false and object.foo', ['object' => $object], false],
            ['false && object.foo', ['object' => $object], false],
            ['true || object.foo', ['object' => $object], true],
            ['true or object.foo', ['object' => $object], true],
        ];
    }

    /**
     * @return array
     */
    public function shortCircuitProviderCompileDataProvider()
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
        $cacheMock = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $expressionLanguage = new ExpressionLanguage($cacheMock);
        $key = 'a%20%2B%20b%2F%2Fa%7CB%3Ab';

        $cacheMock
            ->expects($this->exactly(2))
            ->method('getItem')
            ->with($key)
            ->willReturn($cacheItemMock);
        $cacheItemMock
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnCallback(function () use (&$savedParsedExpressions, $key) {
                return isset($savedParsedExpressions[$key]) ? $savedParsedExpressions[$key] : null;
            }));
        $cacheItemMock
            ->expects($this->exactly(1))
            ->method('set')
            ->with($this->isInstanceOf(ParsedExpression::class))
            ->will($this->returnCallback(function ($expression) use (&$savedParsedExpressions, $key) {
                $savedParsedExpressions[$key] = $expression;
            }));
        $cacheMock
            ->expects($this->exactly(1))
            ->method('save')
            ->with($cacheItemMock);

        $expression = 'a + b';
        $expressionLanguage->compile($expression, ['a', 'B' => 'b']);
        $expressionLanguage->compile($expression, ['B' => 'b', 'a']);
    }
}
