<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Layout\Serializer\ExpressionNormalizer;
use Oro\Component\Layout\ExpressionLanguage\ExpressionLanguageCache;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SerializedParsedExpression;

class ExpressionNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionNormalizer */
    protected $normalizer;

    /** @var ExpressionNormalizer|MockObject */
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(ExpressionLanguageCache::class);
        $this->normalizer = new ExpressionNormalizer($this->cache);
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization((object)[]));
        $this->assertTrue($this->normalizer->supportsNormalization(
            $this->createMock(ParsedExpression::class)
        ));
    }

    public function testNormalize()
    {
        $expression = '5';

        $parsedExpression = $this->createMock(ParsedExpression::class);

        $nodes = $this->createMock(ConstantNode::class);

        $parsedExpression->expects($this->once())->method('getNodes')->willReturn($nodes);
        $parsedExpression->expects($this->once())->method('__toString')->willReturn($expression);

        $expected = [
            'expression' => $expression,
            'nodes' => serialize($nodes)
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($parsedExpression));
    }

    public function testNormalizeCached()
    {
        $expression = '5';

        $parsedExpression = $this->createMock(ParsedExpression::class);
        $parsedExpression->expects($this->once())->method('__toString')->willReturn($expression);

        $this->cache->expects($this->once())
            ->method('getClosure')
            ->with($expression)
            ->willReturn(fn () => 5);

        $this->assertEquals(
            [
                'expression' => $expression,
            ],
            $this->normalizer->normalize($parsedExpression)
        );
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'Object'));
        $this->assertTrue($this->normalizer->supportsDenormalization([], ParsedExpression::class));
    }

    public function testDenormalize()
    {
        $nodes = $this->createMock(ConstantNode::class);

        $data = [
            'expression' => '5',
            'nodes' => serialize($nodes)
        ];

        $parsedExpression = new SerializedParsedExpression('5', serialize($nodes));

        $this->assertEquals($parsedExpression, $this->normalizer->denormalize($data, ParsedExpression::class));
    }

    public function testDenormalizeCached()
    {
        $expression = '5';
        $data = [
            'expression' => '5',
        ];

        $closure = fn () => 5;
        $this->cache->expects($this->once())
            ->method('getClosure')
            ->with($expression)
            ->willReturn($closure);

        $this->assertEquals($closure, $this->normalizer->denormalize($data, ParsedExpression::class));
    }
}
