<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\View;

use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ScrollDataTest extends \PHPUnit\Framework\TestCase
{
    private ScrollData $scrollData;

    protected function setUp(): void
    {
        $this->scrollData = new ScrollData();
    }

    public function testSetGetData()
    {
        $this->assertEmpty($this->scrollData->getData());

        $data = ['some' => 'fields'];
        $this->scrollData->setData($data);
        $this->assertEquals($data, $this->scrollData->getData());
    }

    /**
     * @dataProvider addBlockDataProvider
     */
    public function testAddBlock(
        array $expected,
        string $title,
        int $priority = null,
        string $class = null,
        bool $useSubBlockDivider = true
    ) {
        $this->assertEquals(0, $this->scrollData->addBlock($title, $priority, $class, $useSubBlockDivider));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    public function addBlockDataProvider(): array
    {
        return [
            'minimum parameters' => [
                'expected' => [
                    ScrollData::DATA_BLOCKS => [
                        [
                            ScrollData::TITLE => 'test title',
                            ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                            ScrollData::SUB_BLOCKS => [],
                        ]
                    ]
                ],
                'title' => 'test title',
            ],
            'maximum parameters' => [
                'expected' => [
                    ScrollData::DATA_BLOCKS => [
                        [
                            ScrollData::TITLE => 'test title',
                            ScrollData::PRIORITY => 25,
                            ScrollData::BLOCK_CLASS => 'active',
                            ScrollData::USE_SUB_BLOCK_DIVIDER => false,
                            ScrollData::SUB_BLOCKS => [],
                        ]
                    ]
                ],
                'title' => 'test title',
                'priority' => 25,
                'class' => 'active',
                'useSubBlockDivider' => false,
            ],
        ];
    }

    /**
     * @dataProvider addSubBlockDataProvider
     */
    public function testAddSubBlock(array $source, array $expected, int $blockId, string $title = null)
    {
        $this->scrollData->setData($source);
        $this->assertEquals(0, $this->scrollData->addSubBlock($blockId, $title));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    public function addSubBlockDataProvider(): array
    {
        $source = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [],
                ],
                1 => [
                    ScrollData::TITLE => 'test title 1',
                    ScrollData::SUB_BLOCKS => [],
                ]
            ]
        ];

        $expectedFirst = $source;
        $expectedFirst[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][]
            = [ScrollData::DATA => []];

        $expectedSecond = $source;
        $expectedSecond[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][]
            = [ScrollData::TITLE => 'subblock title', ScrollData::DATA => []];

        return [
            'add to first block' => [
                'source' => $source,
                'expected' => $expectedFirst,
                'blockId' => 0,
            ],
            'add to second block' => [
                'source' => $source,
                'expected' => $expectedSecond,
                'blockId' => 1,
                'title' => 'subblock title'
            ],
        ];
    }

    /**
     * @dataProvider addSubBlockAsFirstDataProvider
     */
    public function testAddSubBlockAsFirst(array $source, array $expected, int $blockId, string $title = null)
    {
        $this->scrollData->setData($source);
        $this->assertEquals(0, $this->scrollData->addSubBlockAsFirst($blockId, $title));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    public function addSubBlockAsFirstDataProvider(): array
    {
        $source = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [

                    ],
                ],
                1 => [
                    ScrollData::TITLE => 'test title 1',
                    ScrollData::SUB_BLOCKS => [
                        0 => ['title' => 'existing block', ScrollData::DATA => []]
                    ],
                ]
            ]
        ];

        $expectedFirst = $source;
        $expectedFirst[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][]
            = [ScrollData::DATA => []];

        $expectedSecond = $source;
        $expectedSecond[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS] = [
            [ScrollData::TITLE => 'subblock title', ScrollData::DATA => []],
            [ScrollData::TITLE => 'existing block', ScrollData::DATA => []]
        ];

        return [
            'add to empty block' => [
                'source' => $source,
                'expected' => $expectedFirst,
                'blockId' => 0,
            ],
            'add to block with existing subblock' => [
                'source' => $source,
                'expected' => $expectedSecond,
                'blockId' => 1,
                'title' => 'subblock title'
            ],
        ];
    }

    public function addSubBlockDataDataProvider(): array
    {
        $initialData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => ['some data']
                        ],
                    ],
                ],
            ]
        ];

        $html = 'another data';
        $expectedFieldNameData = $initialData;
        $expectedData = $initialData;
        $expectedData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][] = $html;

        $expectedData0 = $initialData;
        $expectedData0[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][0] = $html;

        $fieldName = 'someFieldName';
        $expectedFieldNameData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][$fieldName]
            = $html;

        return [
            [
                'html' => $html,
                'fieldName' => null,
                'expectedId' => 1,
                'initialData' => $initialData,
                'expectedData' => $expectedData
            ],
            [
                'html' => $html,
                'fieldName' => 0,
                'expectedId' => 0,
                'initialData' => $initialData,
                'expectedData' => $expectedData0
            ],
            [
                'html' => $html,
                'fieldName' => $fieldName,
                'expectedId' => $fieldName,
                'initialData' => $initialData,
                'expectedData' => $expectedFieldNameData
            ]
        ];
    }

    /**
     * @dataProvider addSubBlockDataDataProvider
     */
    public function testAddSubBlockData(
        string $html,
        string|int|null $fieldName,
        string|int $expectedId,
        array $initialData,
        array $expectedData
    ) {
        $this->scrollData->setData($initialData);
        $this->assertEquals($expectedId, $this->scrollData->addSubBlockData(0, 0, $html, $fieldName));
        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    public function testAddSubBlockException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Block 0 is not defined');

        $this->scrollData->setData([ScrollData::DATA_BLOCKS => []]);
        $this->scrollData->addSubBlock(0);
    }

    public function testAddSubBlockDataNoBlockException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Block 0 is not defined');

        $this->scrollData->setData([ScrollData::DATA_BLOCKS => []]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }

    public function testAddSubBlockDataNoSubBlockException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Subblock 0 is not defined');

        $this->scrollData->setData([ScrollData::DATA_BLOCKS => [0 => [ScrollData::SUB_BLOCKS => []]]]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }

    public function addNamedBlockDataProvider(): array
    {
        $someBlock = [
            'title' => 'SomeBlock title',
            'useSubBlockDivider' => true,
            'priority' => 1,
            'class' => 'SomeClass',
            'subblocks' => []
        ];

        $newBlock = [
            'title' => 'NewBlock title',
            'useSubBlockDivider' => true,
            'priority' => 55,
            'class' => 'Class',
            'subblocks' => []
        ];

        return [
            'add new block' => [
                'blockName' => 'NewBlock',
                'title' => 'NewBlock title',
                'priority' => 55,
                'class' => 'Class',
                'useDivider' => true,
                'initialData' => [
                    'dataBlocks' => [
                        $someBlock,
                    ]
                ],
                'expectedData' => [
                    'dataBlocks' => [
                        $someBlock,
                        'NewBlock' => $newBlock
                    ]
                ]
            ],
            'update existing block' => [
                'blockName' => 'NewBlock',
                'title' => 'NewBlock title',
                'priority' => 55,
                'class' => 'Class',
                'useDivider' => true,
                'initialData' => [
                    'dataBlocks' => [
                        'NewBlock' => [
                            'title' => 'OldBlock title',
                            'useSubBlockDivider' => false,
                            'priority' => 77,
                            'class' => 'Old Class',
                            'subblocks' => []
                        ]
                    ]
                ],
                'expectedData' => [
                    'dataBlocks' => [
                        'NewBlock' => $newBlock
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider addNamedBlockDataProvider
     */
    public function testAddNamedBlock(
        string $blockName,
        string $title,
        ?int $priority,
        ?string $class,
        bool $useDivider,
        array $initialData,
        array $expectedData
    ) {
        $this->scrollData->setData($initialData);
        $this->scrollData->addNamedBlock($blockName, $title, $priority, $class, $useDivider);

        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    public function removeNamedBlockDataProvider(): array
    {
        $newBlock = [
            'title' => 'OldBlock title',
            'useSubBlockDivider' => false,
            'priority' => 77,
            'class' => 'Old Class',
            'subblocks' => []
        ] ;

        return [
            'remove not existing block' => [
                'blockName' => 'NotExistingBlock',
                'initialData' => [
                    'dataBlocks' => ['NewBlock' => $newBlock]
                ],
                'expectedData' => [
                    'dataBlocks' => ['NewBlock' => $newBlock]
                ]
            ],
            'remove existing block' => [
                'blockName' => 'NewBlock',
                'initialData' => [
                    'dataBlocks' => ['NewBlock' => $newBlock]
                ],
                'expectedData' => [
                    'dataBlocks' => []
                ]
            ]
        ];
    }

    /**
     * @dataProvider removeNamedBlockDataProvider
     */
    public function testRemoveNamedBlock(string $blockName, array $initialData, array $expectedData)
    {
        $this->scrollData->setData($initialData);
        $this->scrollData->removeNamedBlock($blockName);
        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    public function hasNamedFieldDataProvider(): array
    {
        $blockData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'another data',
                                'someFieldName' => 'some data'
                            ]
                        ],
                    ],
                ],
            ]
        ];

        return [
            [
                'blockData' => $blockData,
                'fieldName' => 'notExisting',
                'isExistingBlock' => false
            ],
            [
                'blockData' => $blockData,
                'fieldName' => 'someFieldName',
                'isExistingBlock' => true
            ],
        ];
    }

    /**
     * @dataProvider hasNamedFieldDataProvider
     */
    public function testHasNamedField(array $blockData, string $fieldName, bool $isExistingBlock)
    {
        $this->scrollData->setData($blockData);
        $this->assertEquals($isExistingBlock, $this->scrollData->hasNamedField($fieldName));
    }

    public function moveFieldToBlockDataProvider(): array
    {
        $blockData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                'another data',
                            ]
                        ],
                        1 => [
                            ScrollData::DATA => [
                                'someFieldName' => 'some data'
                            ]
                        ],
                    ],
                ],
                1 => [
                    ScrollData::TITLE => 'test title 1',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $expectedData = $blockData;
        unset($expectedData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][1][ScrollData::DATA]['someFieldName']);
        $expectedData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['someFieldName']
            = 'some data';

        return [
            'move field to not existing block' => [
                'blocksData' => $blockData,
                'expectedData' => $blockData,
                'blockId' => 3,
                'fieldId' => 'someFieldName'
            ],
            'move not existing field' => [
                'blocksData' => $blockData,
                'expectedData' => $blockData,
                'blockId' => 0,
                'fieldId' => 'notExistingField'
            ],
            'move field to same block' => [
                'blocksData' => $blockData,
                'expectedData' => $blockData,
                'blockId' => 0,
                'fieldId' => 'someFieldName'
            ],
            'move field to another block' => [
                'blocksData' => $blockData,
                'expectedData' => $expectedData,
                'blockId' => 1,
                'fieldId' => 'someFieldName'
            ],
        ];
    }

    /**
     * @dataProvider moveFieldToBlockDataProvider
     */
    public function testMoveFieldToBlock(array $blocksData, array $expectedData, string|int $blockId, string $fieldId)
    {
        $this->scrollData->setData($blocksData);
        $this->scrollData->moveFieldToBlock($fieldId, $blockId);
        $this->assertEquals($expectedData, $this->scrollData->getData());
    }

    public function testGetBlockIdsWhenBlocksAreEmpty()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => []
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([], $this->scrollData->getBlockIds());
    }

    public function testGetBlockIdsWhenBlocksAreNotEmpty()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => []
                ],
                'namedBlock' => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => []
                ],
            ]
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([0, 'namedBlock'], $this->scrollData->getBlockIds());
    }

    public function testGetSubblockIdsWhenBlockNotExists()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => []
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([], $this->scrollData->getBlockIds());
    }

    public function testGetSubblockIdsWhenBlockExists()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                5 => [
                    ScrollData::TITLE => 'test title 0',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => ['some data']
                        ],
                        'namedSubblock' => [
                            ScrollData::DATA => ['some data']
                        ],
                    ]
                ],
            ]
        ];

        $this->scrollData->setData($blocks);
        $this->assertEquals([0, 'namedSubblock'], $this->scrollData->getSubblockIds(5));
    }

    public function testChangeBlockWhenNoBlockExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Block with id "someId" has not been found');

        $blocks = [
            ScrollData::DATA_BLOCKS => [
            ]
        ];

        $this->scrollData->setData($blocks);
        $this->scrollData->changeBlock('someId');
    }

    public function testChangeBlock()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'test title',
                    ScrollData::PRIORITY => 25,
                    ScrollData::BLOCK_CLASS => 'active',
                    ScrollData::USE_SUB_BLOCK_DIVIDER => false,
                ],
            ]
        ];

        $this->scrollData->setData($blocks);
        $this->scrollData->changeBlock(0, 'New title', 7, 'inactive', true);

        $expectedBlocks = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'New title',
                    ScrollData::PRIORITY => 7,
                    ScrollData::BLOCK_CLASS => 'inactive',
                    ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                ],
            ]
        ];

        $this->assertEquals($expectedBlocks, $this->scrollData->getData());
    }

    public function testHasBlockWhenNoBlock()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
            ]
        ];

        $this->scrollData->setData($blocks);

        $this->assertFalse($this->scrollData->hasBlock(0));
    }

    public function testHasBlock()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::TITLE => 'Title',
                ],
                'named' => [
                    ScrollData::TITLE => 'Named',
                ]
            ]
        ];

        $this->scrollData->setData($blocks);

        $this->assertTrue($this->scrollData->hasBlock(0));
        $this->assertTrue($this->scrollData->hasBlock('named'));
    }

    public function testIsEmptyBlockTrue()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                'empty_named_block' => [
                    ScrollData::TITLE => 'Named',
                ]
            ]
        ];

        $this->scrollData->setData($blocks);

        $this->assertTrue($this->scrollData->isEmptyBlock('empty_named_block'));
    }

    public function testNotEmptyBlock()
    {
        $blocks = [
            ScrollData::DATA_BLOCKS => [
                'not_empty_named_block' => [
                    ScrollData::TITLE => 'Named',
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => ['some data']
                        ],
                        'namedSubblock' => [
                            ScrollData::DATA => ['some data']
                        ],
                    ]
                ]
            ]
        ];

        $this->scrollData->setData($blocks);

        $this->assertFalse($this->scrollData->isEmptyBlock('not_empty_named_block'));
    }

    public function testGetBlockWhenNoBlockExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Block with id "someId" has not been found');

        $this->scrollData->setData([ScrollData::DATA_BLOCKS => []]);
        $this->scrollData->getBlock('someId');
    }

    public function testGetBlock()
    {
        $block = [
            ScrollData::TITLE => 'test title',
            ScrollData::PRIORITY => 25,
            ScrollData::BLOCK_CLASS => 'active',
            ScrollData::USE_SUB_BLOCK_DIVIDER => false,
        ];

        $this->scrollData->setData([ScrollData::DATA_BLOCKS => ['someId' => $block]]);

        $this->assertEquals($block, $this->scrollData->getBlock('someId'));
    }
}
