<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Config;

use Oro\Bundle\FormBundle\Config\BlockConfig;
use Oro\Bundle\FormBundle\Config\SubBlockConfig;

class BlockConfigTest extends \PHPUnit\Framework\TestCase
{
    /** @var BlockConfig */
    private $blockConfig;

    private $blockCode = 'datagrid';

    private $testCode = 'testCode';
    private $testTitle = 'testTitle';
    private $testDescription = 'testDescription';

    private $testClass = 'Test\Class';

    private $testBlockConfig = [
        'block_config' => [
            'type' => [
                'title'     => 'Doctrine Type',
                'priority'  => 1,
                'subblocks' => [
                    'common' => [
                        'title'    => 'Common Setting',
                        'priority' => 1,
                        'useSpan'  => true
                    ],
                    'custom' => [
                        'title'    => 'Custom Setting',
                        'priority' => 2,
                        'useSpan'  => true
                    ],
                ]
            ],
        ]
    ];

    private $testSubBlocks = [];

    private $testSubBlocksConfig = [
        'common' => [
            'title'       => 'Common Setting',
            'priority'    => 3,
            'description' => 'some description',
            'descriptionStyle' => 'some description style',
            'useSpan'     => true,
            'tooltip'     => 'some tooltip'
        ],
        'custom' => [
            'title'    => 'Custom Setting',
            'priority' => 2,
            'useSpan'  => true
        ],
        'last'   => [
            'title'    => 'Last SubBlock',
            'priority' => 1,
            'useSpan'  => true
        ]
    ];

    protected function setUp(): void
    {
        $this->blockConfig = new BlockConfig($this->blockCode);
    }

    public function testProperties()
    {
        /** test getCode */
        $this->assertEquals($this->blockCode, $this->blockConfig->getCode());

        /** test setCode */
        $this->blockConfig->setCode($this->testCode);
        $this->assertEquals($this->testCode, $this->blockConfig->getCode());

        /** test getTitle */
        $this->assertNull($this->blockConfig->getTitle());

        /** test setTitle */
        $this->blockConfig->setTitle($this->testTitle);
        $this->assertEquals($this->testTitle, $this->blockConfig->getTitle());

        /** test getPriority */
        $this->assertNull($this->blockConfig->getPriority());

        /** test setPriority */
        $this->blockConfig->setPriority(10);
        $this->assertEquals(10, $this->blockConfig->getPriority());

        /** test getClass */
        $this->assertNull($this->blockConfig->getClass());

        /** test setClass */
        $this->blockConfig->setClass($this->testClass);
        $this->assertEquals($this->testClass, $this->blockConfig->getClass());

        /** test getSubBlock */
        $this->assertEquals([], $this->blockConfig->getSubBlocks());

        /** test setSubBlocks */
        $this->blockConfig->setSubBlocks($this->testSubBlocks);
        $this->assertEquals($this->testSubBlocks, $this->blockConfig->getSubBlocks());

        /** test setDescription */
        $this->blockConfig->setDescription($this->testDescription);
        $this->assertEquals($this->testDescription, $this->blockConfig->getDescription());

        /** test hasSubBlock */
        $this->assertFalse($this->blockConfig->hasSubBlock('testSubBlock'));

        $this->assertEquals(
            [
                'title'       => $this->testTitle,
                'class'       => $this->testClass,
                'subblocks'   => [],
                'description' => $this->testDescription,
            ],
            $this->blockConfig->toArray()
        );
    }

    public function testSubBlockProperties()
    {
        /** test setSubBlock */
        $subblocks = [];
        foreach ($this->testSubBlocksConfig as $code => $data) {
            $blockDescription = !empty($data['description']) ? $data['description'] : null;
            $blockDescriptionStyle = !empty($data['descriptionStyle']) ? $data['descriptionStyle'] : null;
            $tooltip = !empty($data['tooltip']) ? $data['tooltip'] : null;
            $subblocks[]      = [
                'code'        => $code,
                'title'       => $data['title'],
                'data'        => ['some_data'],
                'description' => $blockDescription,
                'descriptionStyle' => $blockDescriptionStyle,
                'tooltip'     => $tooltip,
                'useSpan'     => true
            ];
            $subBlock         = new SubBlockConfig($code);

            /** test SubBlockConfig set/get Title/Priority/Code */
            $subBlock->setTitle($data['title']);
            $this->assertEquals($data['title'], $subBlock->getTitle());

            $subBlock->setPriority($data['priority']);
            $this->assertEquals($data['priority'], $subBlock->getPriority());

            $subBlock->setCode($code);
            $this->assertEquals($code, $subBlock->getCode());

            $subBlock->setData(['some_data']);
            $this->assertEquals(['some_data'], $subBlock->getData());

            $subBlock->setUseSpan(true);
            $this->assertTrue($subBlock->getUseSpan());

            $subBlock->setTooltip($tooltip);
            $this->assertEquals($tooltip, $subBlock->getTooltip());

            $subBlock->setDescription($blockDescription);
            $this->assertEquals($blockDescription, $subBlock->getDescription());

            $subBlock->setDescriptionStyle($blockDescriptionStyle);
            $this->assertEquals($blockDescriptionStyle, $subBlock->getDescriptionStyle());

            /** test SubBlockConfig addSubBlock */
            $this->blockConfig->addSubBlock($subBlock);
            $this->assertEquals($subBlock, $this->blockConfig->getSubBlock($code));

            $this->testSubBlocks[] = $subBlock;
        }

        $this->blockConfig->setSubBlocks($this->testSubBlocks);
        $this->assertEquals($this->testSubBlocks, $this->blockConfig->getSubBlocks());

        $this->assertEquals(
            [
                'title'       => null,
                'class'       => null,
                'subblocks'   => $subblocks,
                'description' => null,
            ],
            $this->blockConfig->toArray()
        );
    }

    public function testBlockConfig()
    {
        self::assertNull($this->blockConfig->getBlockConfig());
        $this->blockConfig->setBlockConfig($this->testBlockConfig);
        self::assertEquals($this->testBlockConfig, $this->blockConfig->getBlockConfig());
    }
}
