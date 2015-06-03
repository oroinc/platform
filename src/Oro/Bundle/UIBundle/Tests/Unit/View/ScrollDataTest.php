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
                    'dataBlocks' => [
                        [
                            'title' => 'test title',
                            'useSubBlockDivider' => true,
                            'subblocks' => [],
                        ]
                    ]
                ],
                'title' => 'test title',
            ],
            'maximum parameters' => [
                'expected' => [
                    'dataBlocks' => [
                        [
                            'title' => 'test title',
                            'priority' => 25,
                            'class' => 'active',
                            'useSubBlockDivider' => false,
                            'subblocks' => [],
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
            'dataBlocks' => [
                0 => [
                    'title' => 'test title 0',
                    'subblocks' => [],
                ],
                1 => [
                    'title' => 'test title 1',
                    'subblocks' => [],
                ]
            ]
        ];

        $expectedFirst = $source;
        $expectedFirst['dataBlocks'][0]['subblocks'][] = ['data' => []];

        $expectedSecond = $source;
        $expectedSecond['dataBlocks'][1]['subblocks'][] = ['title' => 'subblock title', 'data' => []];

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
            'dataBlocks' => [
                0 => [
                    'title' => 'test title 0',
                    'subblocks' => [
                        0 => [
                            'data' => ['some data']
                        ],
                    ],
                ],
            ]
        ];

        $expected = $data;
        $expected['dataBlocks'][0]['subblocks'][0]['data'][] = $html;

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
        $this->scrollData->setData(['dataBlocks' => []]);
        $this->scrollData->addSubBlock(0);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Block 0 is not defined
     */
    public function testAddSubBlockDataNoBlockException()
    {
        $this->scrollData->setData(['dataBlocks' => []]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Subblock 0 is not defined
     */
    public function testAddSubBlockDataNoSubBlockException()
    {
        $this->scrollData->setData(['dataBlocks' => [0 => ['subblocks' => []]]]);
        $this->scrollData->addSubBlockData(0, 0, 'html');
    }
}
