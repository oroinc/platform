<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\View;

use Oro\Bundle\UIBundle\View\ScrollData;

class ScrollDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScrollData
     */
    protected $scrollData;

    protected function setUp()
    {
        $this->scrollData = new ScrollData();
    }

    public function testSetGetData()
    {
        $this->assertEmpty($this->scrollData->getData());

        $data = ['some' => 'fields'];
        $this->scrollData->setData($data);
        $this->assertAttributeEquals($data, 'data', $this->scrollData);
        $this->assertEquals($data, $this->scrollData->getData());
    }

    /**
     * @param array $expected
     * @param string $title
     * @param int|null null $priority
     * @param string|null $class
     * @param bool $useSubBlockDivider
     * @dataProvider addBlockDataProvider
     */
    public function testAddBlock(array $expected, $title, $priority = null, $class = null, $useSubBlockDivider = true)
    {
        $this->assertEquals(0, $this->scrollData->addBlock($title, $priority, $class, $useSubBlockDivider));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    /**
     * @return array
     */
    public function addBlockDataProvider()
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
     * @param array $source
     * @param array $expected
     * @param int $blockId
     * @param string|null $title
     * @dataProvider addSubBlockDataProvider
     */
    public function testAddSubBlock(array $source, array $expected, $blockId, $title = null)
    {
        $this->scrollData->setData($source);
        $this->assertEquals(0, $this->scrollData->addSubBlock($blockId, $title));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    /**
     * @return array
     */
    public function addSubBlockDataProvider()
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

    public function testAddSubBlockData()
    {
        $html = 'another data';

        $data = [
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

        $expected = $data;
        $expected[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][] = $html;

        $this->scrollData->setData($data);
        $this->assertEquals(1, $this->scrollData->addSubBlockData(0, 0, $html));
        $this->assertEquals($expected, $this->scrollData->getData());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Block 0 is not defined
     */
    public function testAddSubBlockException()
    {
        $this->scrollData->setData([ScrollData::DATA_BLOCKS => []]);
        $this->scrollData->addSubBlock(0);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Block 0 is not defined
     */
    public function testAddSubBlockDataNoBlockException()
    {
        $this->scrollData->setData([ScrollData::DATA_BLOCKS => []]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Subblock 0 is not defined
     */
    public function testAddSubBlockDataNoSubBlockException()
    {
        $this->scrollData->setData([ScrollData::DATA_BLOCKS => [0 => [ScrollData::SUB_BLOCKS => []]]]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }
}
