<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

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

    /**
     * @dataProvider normalizeWithoutObjectsInVarsProvider
     *
     * @param array $expectedResult
     * @param BlockView $actualView
     */
    public function testNormalizeWithoutObjectsInVars(array $expectedResult, BlockView $actualView)
    {
        $this->assertEquals($expectedResult, $this->normalizer->normalize($actualView));
    }

    public function normalizeWithoutObjectsInVarsProvider()
    {
        return [
            'single view without vars' => [
                'expectedResult' => [
                    'vars' => [
                        'attr' => [],
                    ]
                ],
                'actualView' => $this->createBlockView()
            ],
            'single view with vars' => [
                'expectedResult' => [
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar'
                    ]
                ],
                'actualView' => $this->createBlockView([
                    'foo' => 'bar'
                ])
            ],
            'view with children' => [
                'expectedResult' => [
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar'
                    ],
                    'children' => [
                        [
                            'vars' => [
                                'attr' => [],
                            ],
                            'children' => [
                                [
                                    'vars' => [
                                        'attr' => [],
                                        'title' => 'test'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'actualView' => $this->createBlockView(
                    [
                        'attr' => [],
                        'foo' => 'bar'
                    ],
                    [
                        $this->createBlockView(
                            [],
                            [$this->createBlockView(['title' => 'test'])]
                        )
                    ]
                )
            ]
        ];
    }

    public function testNormalizeWithObjectsInVars()
    {
        $bar = (object)[];

        $view = new BlockView();
        $view->vars = [
            'foo' => [
                'bar' => $bar
            ]
        ];

        $this->serializer->expects($this->once())
            ->method('normalize')
            ->with($bar)
            ->willReturn('serialized data');

        $expected = [
            'vars' => [
                'foo' => [
                    'bar' => [
                        'type' => get_class($bar),
                        'value' => 'serialized data'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($view));
    }

    /**
     * @expectedException \Oro\Bundle\LayoutBundle\Exception\UnexpectedBlockViewVarTypeException
     */
    public function testNormalizeShouldFailOnBlockViewInVars()
    {
        $view = new BlockView();
        $view->vars = [
            'foo' => new BlockView()
        ];

        $this->normalizer->normalize($view);
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'Object'));
        $this->assertTrue($this->normalizer->supportsDenormalization([], BlockView::class));
    }

    /**
     * @dataProvider denormalizeWithoutObjectsInVarsProvider
     *
     * @param BlockView $expectedView
     * @param array $actualData
     */
    public function testDenormalizeWithoutObjectsInVars(BlockView $expectedView, array $actualData)
    {
        $this->assertEquals(
            $expectedView,
            $this->normalizer->denormalize($actualData, BlockView::class)
        );
    }

    public function denormalizeWithoutObjectsInVarsProvider()
    {
        $viewWithChildren11 = $this->createBlockView(['title' => 'test']);
        $viewWithChildren1 = $this->createBlockView([], [$viewWithChildren11]);
        $viewWithChildren = $this->createBlockView(
            ['foo' => 'bar'],
            [$viewWithChildren1]
        );

        $viewWithChildrenBlocks = [
            $viewWithChildren,
            $viewWithChildren11,
            $viewWithChildren1,
        ];

        foreach ($viewWithChildrenBlocks as $view) {
            $view->blocks = $view->vars['blocks'] = $viewWithChildrenBlocks;
        }

        return [
            'single view without vars' => [
                'expectedView' => $this->createBlockView(),
                'actualData' => []
            ],
            'single view with vars' => [
                'expectedView' => $this->createBlockView([
                    'foo' => 'bar'
                ]),
                'actualData' => [
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar'
                    ]
                ]
            ],
            'view with children' => [
                'expectedView' => $viewWithChildren,
                'actualData' => [
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar',
                    ],
                    'children' => [
                        [
                            'vars' => [
                                'attr' => [],
                            ],
                            'children' => [
                                [
                                    'vars' => [
                                        'attr' => [],
                                        'title' => 'test'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    public function testDenormalizeWithObjectsInVars()
    {
        $bar = (object)[];

        $data = [
            'vars' => [
                'foo' => [
                    'bar' => [
                        'type' => get_class($bar),
                        'value' => 'serialized data'
                    ]
                ]
            ]
        ];

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with('serialized data', get_class($bar))
            ->willReturn($bar);

        $expectedView = new BlockView();
        $expectedView->vars = [
            'block' => $expectedView,
            'foo' => [
                'bar' => $bar
            ]
        ];

        $expectedView->blocks = $expectedView->vars['blocks'] = [$expectedView];

        $this->assertEquals(
            $expectedView,
            $this->normalizer->denormalize($data, BlockView::class)
        );
    }

    /**
     * @param array $vars
     * @param BlockView[] $children
     * @return BlockView
     */
    protected function createBlockView(array $vars = [], array $children = [])
    {
        $view = new BlockView();
        $view->blocks = [$view];
        $view->vars = array_merge($vars, [
            'attr' => [],
            'block' => $view,
            'blocks' => [$view]
        ]);
        $view->children = $children;
        foreach ($children as $child) {
            $child->parent = $view;
        }

        return $view;
    }
}
