<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use Oro\Bundle\LayoutBundle\Layout\Serializer\ExpressionNormalizer;

class ExpressionNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->normalizer = new ExpressionNormalizer();
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization((object)[]));
        $this->assertTrue($this->normalizer->supportsNormalization(
            $this->getMock(ParsedExpression::class, [], [], '', false)
        ));
    }

    public function testNormalize()
    {
        $expression = '5';

        $parsedExpression = $this->getMock(ParsedExpression::class, [], [], '', false);

        $nodes = $this->getMock(ConstantNode::class, [], [], '', false);

        $parsedExpression->expects($this->once())->method('getNodes')->willReturn($nodes);
        $parsedExpression->expects($this->once())->method('__toString')->willReturn($expression);

        $expected = [
            'expression' => $expression,
            'nodes' => serialize($nodes)
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($parsedExpression));
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'Object'));
        $this->assertTrue($this->normalizer->supportsDenormalization([], ParsedExpression::class));
    }

    public function testDenormalize()
    {
        $nodes = $this->getMock(ConstantNode::class, [], [], '', false);

        $data = [
            'expression' => '5',
            'nodes' => serialize($nodes)
        ];

        $parsedExpression = new ParsedExpression('5', $nodes);

        $this->assertEquals($parsedExpression, $this->normalizer->denormalize($data, ParsedExpression::class));
    }
}
