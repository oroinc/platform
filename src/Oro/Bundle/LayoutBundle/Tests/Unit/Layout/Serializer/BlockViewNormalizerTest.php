<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewNormalizer;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCollection;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class BlockViewNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NormalizerInterface|DenormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $serializer;

    /** @var BlockViewNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->serializer = $this->createMock(Serializer::class);

        $this->normalizer = new BlockViewNormalizer();
        $this->normalizer->setSerializer($this->serializer);
    }

    public function testSupportsNormalization()
    {
        $this->assertFalse($this->normalizer->supportsNormalization((object)[]));
        $this->assertTrue($this->normalizer->supportsNormalization(
            $this->createMock(BlockView::class)
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

    /**
     * @return array
     */
    public function normalizeWithoutObjectsInVarsProvider()
    {
        return [
            'single view without vars' => [
                'expectedResult' => [
                    'vars' => [
                        'id' => 'root',
                    ],
                ],
                'actualView' => $this->createBlockView('root')
            ],
            'single view with vars' => [
                'expectedResult' => [
                    'vars' => [
                        'id' => 'root',
                        'foo' => 'bar',
                    ]
                ],
                'actualView' => $this->createBlockView('root', [
                    'foo' => 'bar'
                ])
            ],
            'view with children' => [
                'expectedResult' => [
                    'vars' => [
                        'id' => 'root',
                        'foo' => 'bar',
                        'attr' => [],
                    ],
                    'children' => [
                        [
                            'vars' => [
                                'id' => 'child1',
                            ],
                            'children' => [
                                [
                                    'vars' => [
                                        'id' => 'child11',
                                        'title' => 'test'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'actualView' => $this->createBlockView(
                    'root',
                    [
                        'attr' => [],
                        'foo' => 'bar'
                    ],
                    [
                        $this->createBlockView(
                            'child1',
                            [],
                            [$this->createBlockView('child11', ['title' => 'test'])]
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
            'id' => 'root',
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
                'id' => 'root',
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

    /**
     * @return array
     */
    public function denormalizeWithoutObjectsInVarsProvider()
    {
        $child11 = $this->createBlockView('child11', ['title' => 'test']);
        $child1 = $this->createBlockView('child1', [], [$child11]);
        $root = $this->createBlockView(
            'root',
            ['foo' => 'bar'],
            [$child1]
        );

        $blocks = [
            'root' => $root,
            'child1' => $child1,
            'child11' => $child11,
        ];

        foreach ($blocks as $view) {
            $view->blocks = $view->vars['blocks'] = new BlockViewCollection($blocks);
        }

        return [
            'single view without vars' => [
                'expectedView' => $this->createBlockView('root'),
                'actualData' => [
                    'vars' => [
                        'id' => 'root',
                    ]
                ]
            ],
            'single view with vars' => [
                'expectedView' => $this->createBlockView('root', [
                    'foo' => 'bar'
                ]),
                'actualData' => [
                    'vars' => [
                        'id' => 'root',
                        'foo' => 'bar'
                    ]
                ]
            ],
            'view with children' => [
                'expectedView' => $root,
                'actualData' => [
                    'vars' => [
                        'id' => 'root',
                        'foo' => 'bar',
                    ],
                    'children' => [
                        [
                            'vars' => [
                                'id' => 'child1',
                            ],
                            'children' => [
                                [
                                    'vars' => [
                                        'id' => 'child11',
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
                'id' => 'root',
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
            'id' => 'root',
            'block' => $expectedView,
            'foo' => [
                'bar' => $bar
            ]
        ];

        $expectedView->blocks = $expectedView->vars['blocks'] = new BlockViewCollection([
            'root' => $expectedView
        ]);

        $this->assertEquals(
            $expectedView,
            $this->normalizer->denormalize($data, BlockView::class)
        );
    }

    /**
     * @param string      $id
     * @param array       $vars
     * @param BlockView[] $children
     * @return BlockView
     */
    protected function createBlockView($id, array $vars = [], array $children = [])
    {
        $view = new BlockView();
        $view->blocks = new BlockViewCollection([$id => $view]);
        $view->vars = array_merge($vars, [
            'id' => $id,
            'block' => $view,
            'blocks' => $view->blocks
        ]);
        $view->children = $children;
        foreach ($children as $child) {
            $child->parent = $view;
        }

        return $view;
    }
}
