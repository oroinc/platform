<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit;

use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;
use Symfony\Component\ExpressionLanguage\Tests\Fixtures\TestProvider;

class ExpressionLanguageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var ParserCacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheMock;

    public function setUp()
    {
        $this->cacheMock = $this->getMock(ParserCacheInterface::class);
        $this->expressionLanguage = new ExpressionLanguage($this->cacheMock);
    }

    public function testCachedParse()
    {
        $savedParsedExpression = null;

        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('fetch')
            ->with('1 + 1//')
            ->will($this->returnCallback(function () use (&$savedParsedExpression) {
                return $savedParsedExpression;
            }));
        $this->cacheMock
            ->expects($this->exactly(1))
            ->method('save')
            ->with('1 + 1//', $this->isInstanceOf('Symfony\Component\ExpressionLanguage\ParsedExpression'))
            ->will($this->returnCallback(function ($key, $expression) use (&$savedParsedExpression) {
                $savedParsedExpression = $expression;
            }));

        $parsedExpression = $this->expressionLanguage->parse('1 + 1', []);
        $this->assertSame($savedParsedExpression, $parsedExpression);

        $parsedExpression = $this->expressionLanguage->parse('1 + 1', []);
        $this->assertSame($savedParsedExpression, $parsedExpression);
    }

    public function testConstantFunction()
    {
        $expression = 'constant("PHP_VERSION")';
        $this->assertEquals(PHP_VERSION, $this->expressionLanguage->evaluate($expression));
        $this->assertEquals($expression, $this->expressionLanguage->compile($expression));
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
        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('fetch')
            ->will($this->returnCallback(function ($key) use (&$savedParsedExpressions) {
                return isset($savedParsedExpressions[$key]) ? $savedParsedExpressions[$key] : null;
            }));
        $this->cacheMock
            ->expects($this->exactly(1))
            ->method('save')
            ->will($this->returnCallback(function ($key, $expression) use (&$savedParsedExpressions) {
                $savedParsedExpressions[$key] = $expression;
            }));

        $expression = 'a + b';
        $this->expressionLanguage->compile($expression, ['a', 'B' => 'b']);
        $this->expressionLanguage->compile($expression, ['B' => 'b', 'a']);
    }
}
