<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

use Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewNormalizer;
use Oro\Component\Layout\BlockView;

class BlockViewNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var NormalizerInterface|DenormalizerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var BlockViewNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->serializer = $this->getMock(Serializer::class, [], [], '', false);

        $this->normalizer = new BlockViewNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization((object)[]));
        $this->assertTrue($this->normalizer->supportsNormalization(
            $this->getMock(BlockView::class)
        ));
    }

    public function testNormalize()
    {
        $barExpr = $this->getMock(ParsedExpression::class, [], [], '', false);

        $rootView = new BlockView();
        $rootView->vars = [
            'foo' => [
                'bar' => $barExpr
            ]
        ];

        $headerView = new BlockView();
        $headerView->vars = ['title' => 'test title'];

        $rootView->children = [$headerView];

        $this->serializer->expects($this->once())
            ->method('normalize')
            ->with($barExpr)
            ->willReturn('serialized data');

        $expected = [ //root
            'vars' => [
                'foo' => [
                    'bar' => [
                        'type' => get_class($barExpr),
                        'value' => 'serialized data'
                    ]
                ]
            ],
            'children' => [
                [ //header
                    'vars' => [
                        'title' => 'test title'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($rootView));
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'Object'));
        $this->assertTrue($this->normalizer->supportsDenormalization([], BlockView::class));
    }

    public function testDenormalize()
    {
        $barExpr = $this->getMock(ParsedExpression::class, [], [], '', false);

        $data = [ //root
            'vars' => [
                'foo' => [
                    'bar' => [
                        'type' => get_class($barExpr),
                        'value' => 'serialized data'
                    ]
                ]
            ],
            'children' => [
                [ //header
                    'vars' => [
                        'title' => 'test title'
                    ]
                ]
            ]
        ];

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with('serialized data', get_class($barExpr))
            ->willReturn($barExpr);

        $expectedRootView = new BlockView();
        $expectedRootView->vars = [
            'foo' => [
                'bar' => $barExpr
            ]
        ];

        $expectedHeaderView = new BlockView();
        $expectedHeaderView->parent = $expectedRootView;
        $expectedHeaderView->vars = [
            'title' => 'test title'
        ];

        $expectedRootView->children = [$expectedHeaderView];

        $this->assertEquals(
            $expectedRootView,
            $this->normalizer->denormalize($data, BlockView::class)
        );
    }
}
