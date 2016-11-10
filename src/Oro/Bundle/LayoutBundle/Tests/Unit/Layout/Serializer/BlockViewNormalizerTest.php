<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

use Oro\Component\Layout\ArrayCollection;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewNormalizer;

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
                    'id' => 'root',
                    'vars' => [
                        'attr' => [],
                    ]
                ],
                'actualView' => $this->createBlockView('root')
            ],
            'single view with vars' => [
                'expectedResult' => [
                    'id' => 'root',
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar'
                    ]
                ],
                'actualView' => $this->createBlockView('root', [
                    'foo' => 'bar'
                ])
            ],
            'view with children' => [
                'expectedResult' => [
                    'id' => 'root',
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar'
                    ],
                    'children' => [
                        [
                            'id' => 'child1',
                            'vars' => [
                                'attr' => [],
                            ],
                            'children' => [
                                [
                                    'id' => 'child11',
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

        $view = new BlockView('root');
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
            'id' => 'root',
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
        $view = new BlockView('root');
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
            $view->blocks = $view->vars['blocks'] = new ArrayCollection($blocks);
        }

        return [
            'single view without vars' => [
                'expectedView' => $this->createBlockView('root'),
                'actualData' => [
                    'id' => 'root',
                ]
            ],
            'single view with vars' => [
                'expectedView' => $this->createBlockView('root', [
                    'foo' => 'bar'
                ]),
                'actualData' => [
                    'id' => 'root',
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar'
                    ]
                ]
            ],
            'view with children' => [
                'expectedView' => $root,
                'actualData' => [
                    'id' => 'root',
                    'vars' => [
                        'attr' => [],
                        'foo' => 'bar',
                    ],
                    'children' => [
                        [
                            'id' => 'child1',
                            'vars' => [
                                'attr' => [],
                            ],
                            'children' => [
                                [
                                    'id' => 'child11',
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
            'id' => 'root',
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

        $expectedView = new BlockView('root');
        $expectedView->vars = [
            'block' => $expectedView,
            'foo' => [
                'bar' => $bar
            ]
        ];

        $expectedView->blocks = $expectedView->vars['blocks'] = new ArrayCollection([
            'root' => $expectedView
        ]);

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
    protected function createBlockView($id, array $vars = [], array $children = [])
    {
        $view = new BlockView($id);
        $view->blocks = new ArrayCollection([$id => $view]);
        $view->vars = array_merge($vars, [
            'attr' => [],
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
