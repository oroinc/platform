<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Exception\UnexpectedBlockViewVarTypeException;
use Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewNormalizer;
use Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewVarsNormalizer;
use Oro\Bundle\LayoutBundle\Layout\Serializer\TypeNameConverter;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCollection;
use Symfony\Component\Serializer\Serializer;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BlockViewNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private const CONTEXT_HASH_VALUE = 'context_hash_value';

    /** @var TypeNameConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $typeNameConverter;

    /** @var Serializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var BlockViewNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->typeNameConverter = $this->createMock(TypeNameConverter::class);
        $this->serializer = $this->createMock(Serializer::class);

        $this->normalizer = new BlockViewNormalizer(new BlockViewVarsNormalizer(), $this->typeNameConverter);
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
     */
    public function testNormalizeWithoutObjectsInVars(array $expectedResult, BlockView $actualView)
    {
        $this->assertEquals($expectedResult, $this->normalizer->normalize($actualView));
    }

    public function normalizeWithoutObjectsInVarsProvider(): array
    {
        return [
            'single view without vars' => [
                'expectedResult' => [
                    'k' => ['root', 'block', []]
                ],
                'actualView' => $this->createBlockView('root')
            ],
            'single view with vars' => [
                'expectedResult' => [
                    'k' => ['root', 'block', []],
                    'v' => [
                        'foo' => 'bar'
                    ]
                ],
                'actualView' => $this->createBlockView('root', [
                    'foo' => 'bar'
                ])
            ],
            'view with children' => [
                'expectedResult' => [
                    'k' => ['root', 'container', []],
                    'v' => [
                        'class_prefix' => 'prefix1',
                        'foo' => 'bar'
                    ],
                    'c' => [
                        [
                            'k' => ['child1', 'container', ['prefix1', 'prefix2']],
                            'c' => [
                                [
                                    'k' => ['child11', 'block', []],
                                    'v' => [
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
                        'class_prefix' => 'prefix1',
                        'attr' => [],
                        'foo' => 'bar'
                    ],
                    [
                        $this->createBlockView(
                            'child1',
                            ['block_prefixes' => ['prefix1', 'prefix2']],
                            [$this->createBlockView('child11', ['title' => 'test'])]
                        )
                    ]
                )
            ]
        ];
    }

    public function testNormalizeWithObjectsInVarsWhenNoShortTypeName()
    {
        $bar = (object)[];

        $view = new BlockView();
        $view->vars = [
            'id' => 'root',
            'block_type' => 'container',
            'foo' => [
                'bar' => $bar
            ],
            'class_prefix' => null,
            'block_type_widget_id' => 'container_widget',
            'unique_block_prefix' => '_root',
            'cache_key' => '_root_container_' . self::CONTEXT_HASH_VALUE
        ];

        $this->serializer->expects($this->once())
            ->method('normalize')
            ->with($bar)
            ->willReturn('serialized data');

        $this->typeNameConverter->expects($this->once())
            ->method('getShortTypeName')
            ->with(get_class($bar))
            ->willReturn(null);

        $expected = [
            'k' => ['root', 'container', []],
            'v' => [
                'foo' => [
                    'bar' => ['t' => get_class($bar), 'v' => 'serialized data']
                ]
            ]
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($view));
    }

    public function testNormalizeWithObjectsInVarsWhenHasShortTypeName()
    {
        $bar = (object)[];

        $view = new BlockView();
        $view->vars = [
            'id' => 'root',
            'block_type' => 'container',
            'foo' => [
                'bar' => $bar
            ],
            'class_prefix' => null,
            'block_type_widget_id' => 'container_widget',
            'unique_block_prefix' => '_root',
            'cache_key' => '_root_container_' . self::CONTEXT_HASH_VALUE
        ];

        $this->serializer->expects($this->once())
            ->method('normalize')
            ->with($bar)
            ->willReturn('serialized data');

        $this->typeNameConverter->expects($this->once())
            ->method('getShortTypeName')
            ->with(get_class($bar))
            ->willReturn('c');

        $expected = [
            'k' => ['root', 'container', []],
            'v' => [
                'foo' => [
                    'bar' => ['t' => 'c', 'v' => 'serialized data']
                ]
            ]
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($view));
    }

    public function testNormalizeShouldNotChangeBlockViewToBeNormalized()
    {
        $view = new BlockView();
        $view->vars = [
            'id' => 'root',
            'block_type' => 'container',
            'class_prefix' => null,
            'block_type_widget_id' => 'container_widget',
            'unique_block_prefix' => '_root',
            'cache_key' => '_root_container_' . self::CONTEXT_HASH_VALUE
        ];

        $this->assertEquals(
            [
                'k' => ['root', 'container', []]
            ],
            $this->normalizer->normalize($view)
        );
        $this->assertEquals(
            [
                'id' => 'root',
                'block_type' => 'container',
                'class_prefix' => null,
                'block_type_widget_id' => 'container_widget',
                'unique_block_prefix' => '_root',
                'cache_key' => '_root_container_' . self::CONTEXT_HASH_VALUE
            ],
            $view->vars
        );
    }

    public function testNormalizeShouldFailOnBlockViewInVars()
    {
        $this->expectException(UnexpectedBlockViewVarTypeException::class);
        $this->expectExceptionMessage('BlockView vars cannot contain link to another BlockView');

        $view = new BlockView();
        $view->vars = [
            'id' => 'root',
            'block_type' => 'container',
            'class_prefix' => null,
            'block_type_widget_id' => 'container_widget',
            'unique_block_prefix' => '_root',
            'cache_key' => '_root_container_' . self::CONTEXT_HASH_VALUE,
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
     */
    public function testDenormalizeWithoutObjectsInVars(BlockView $expectedView, array $actualData)
    {
        $this->assertEquals(
            $expectedView,
            $this->normalizer->denormalize(
                $actualData,
                BlockView::class,
                null,
                ['context_hash' => self::CONTEXT_HASH_VALUE]
            )
        );
    }

    public function denormalizeWithoutObjectsInVarsProvider(): array
    {
        $child11 = $this->createBlockView('child11', ['title' => 'test']);
        $child1 = $this->createBlockView(
            'child1',
            ['block_prefixes' => ['prefix1', 'prefix2']],
            ['child11' => $child11]
        );
        $root = $this->createBlockView(
            'root',
            ['foo' => 'bar'],
            ['child1' => $child1]
        );

        $blocks = [
            'root' => $root,
            'child1' => $child1,
            'child11' => $child11,
        ];

        foreach ($blocks as $view) {
            $view->blocks = new BlockViewCollection($blocks);
            $view->vars['blocks'] = $view->blocks;
        }

        return [
            'single view without vars' => [
                'expectedView' => $this->createBlockView('root'),
                'actualData' => [
                    'k' => ['root', 'block', []],
                    'v' => []
                ]
            ],
            'single view with vars' => [
                'expectedView' => $this->createBlockView('root', [
                    'foo' => 'bar'
                ]),
                'actualData' => [
                    'k' => ['root', 'block', []],
                    'v' => [
                        'foo' => 'bar'
                    ]
                ]
            ],
            'view with children' => [
                'expectedView' => $root,
                'actualData' => [
                    'k' => ['root', 'container', []],
                    'v' => [
                        'foo' => 'bar',
                    ],
                    'c' => [
                        [
                            'k' => ['child1', 'container', ['prefix1', 'prefix2']],
                            'v' => [],
                            'c' => [
                                [
                                    'k' => ['child11', 'block', []],
                                    'v' => [
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

    public function testDenormalizeWithObjectsInVarsWithFullTypeName()
    {
        $bar = (object)[];

        $data = [
            'k' => ['root', 'container', []],
            'v' => [
                'foo' => [
                    'bar' => ['t' => get_class($bar), 'v' => 'serialized data']
                ]
            ]
        ];

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with('serialized data', get_class($bar))
            ->willReturn($bar);
        $this->typeNameConverter->expects($this->once())
            ->method('getTypeName')
            ->with(get_class($bar))
            ->willReturn(null);

        $expectedView = new BlockView();
        $expectedView->vars = [
            'id' => 'root',
            'block_type' => 'container',
            'block_prefixes' => [],
            'block' => $expectedView,
            'foo' => [
                'bar' => $bar,
            ],
            'visible' => true,
            'hidden' => false,
            'attr' => [],
            'translation_domain' => 'messages',
            'class_prefix' => null,
            'block_type_widget_id' => 'container_widget',
            'unique_block_prefix' => '_root',
            'cache_key' => '_root_container_' . self::CONTEXT_HASH_VALUE,
            'cache' => null
        ];

        $expectedView->blocks = new BlockViewCollection(['root' => $expectedView]);
        $expectedView->vars['blocks'] = $expectedView->blocks;

        $this->assertEquals(
            $expectedView,
            $this->normalizer->denormalize(
                $data,
                BlockView::class,
                null,
                ['context_hash' => self::CONTEXT_HASH_VALUE]
            )
        );
    }

    public function testDenormalizeWithObjectsInVarsWithShortTypeName()
    {
        $bar = (object)[];

        $data = [
            'k' => ['root', 'container', []],
            'v' => [
                'foo' => [
                    'bar' => ['t' => 'c', 'v' => 'serialized data']
                ]
            ]
        ];

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with('serialized data', get_class($bar))
            ->willReturn($bar);
        $this->typeNameConverter->expects($this->once())
            ->method('getTypeName')
            ->with('c')
            ->willReturn(get_class($bar));

        $expectedView = new BlockView();
        $expectedView->vars = [
            'id' => 'root',
            'block_type' => 'container',
            'block_prefixes' => [],
            'block' => $expectedView,
            'foo' => [
                'bar' => $bar,
            ],
            'visible' => true,
            'hidden' => false,
            'attr' => [],
            'translation_domain' => 'messages',
            'class_prefix' => null,
            'block_type_widget_id' => 'container_widget',
            'unique_block_prefix' => '_root',
            'cache_key' => '_root_container_' . self::CONTEXT_HASH_VALUE,
            'cache' => null
        ];

        $expectedView->blocks = new BlockViewCollection(['root' => $expectedView]);
        $expectedView->vars['blocks'] = $expectedView->blocks;

        $this->assertEquals(
            $expectedView,
            $this->normalizer->denormalize(
                $data,
                BlockView::class,
                null,
                ['context_hash' => self::CONTEXT_HASH_VALUE]
            )
        );
    }

    private function createBlockView(string $id, array $vars = [], array $children = []): BlockView
    {
        $blockType = $children ? 'container' : 'block';
        $view = new BlockView();
        $view->blocks = new BlockViewCollection([$id => $view]);
        $view->vars = array_merge(
            [
                'id' => $id,
                'block_type' => $blockType,
                'block_prefixes' => [],
                'block' => $view,
                'blocks' => $view->blocks,
                'visible' => true,
                'hidden' => false,
                'attr' => [],
                'translation_domain' => 'messages',
                'class_prefix' => null,
                'block_type_widget_id' => $blockType . '_widget',
                'unique_block_prefix' => '_' . $id,
                'cache_key' => '_' . $id . '_' . $blockType . '_' . self::CONTEXT_HASH_VALUE,
                'cache' => null
            ],
            $vars
        );
        $view->children = $children;
        foreach ($children as $child) {
            $child->parent = $view;
        }

        return $view;
    }
}
