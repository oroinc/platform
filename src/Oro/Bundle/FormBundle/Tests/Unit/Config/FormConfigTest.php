<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Config;

use Oro\Bundle\FormBundle\Config\BlockConfig;
use Oro\Bundle\FormBundle\Config\FormConfig;
use Oro\Bundle\FormBundle\Config\SubBlockConfig;
use Oro\Bundle\UserBundle\Entity\User;

class FormConfigTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormConfig */
    private $formConfig;

    private $blocks = [];

    private $testSubBlocksConfig = [
        'common' => [
            'title'    => 'Common Setting',
            'priority' => 3,
        ],
        'custom' => [
            'title'    => 'Custom Setting',
            'priority' => 2,
        ],
        'last' => [
            'title'    => 'Last SubBlock',
            'priority' => 1,
        ]
    ];

    protected function setUp(): void
    {
        $this->formConfig = new FormConfig();
    }

    public function testAddBlock()
    {
        /** test getBlocks without any adjusted block(s) */
        $this->assertEquals([], $this->formConfig->getBlocks());

        /** test hasBlock without any adjusted block(s) */
        $this->assertFalse($this->formConfig->hasBlock('testBlock'));

        $blockConfig = new BlockConfig('testBlock');
        $blockConfig
            ->setTitle('Test Block')
            ->setClass(User::class)
            ->setPriority(1);

        $subBlocks      = [];
        $subBlocksArray = [];
        foreach ($this->testSubBlocksConfig as $code => $data) {
            $subBlock = new SubBlockConfig($code);
            $subBlock
                ->setTitle($data['title'])
                ->setPriority($data['priority']);
            $blockConfig->addSubBlock($subBlock);

            $subBlocks[] = $subBlock;
            $subBlocksArray[] = $subBlock->toArray();
        }

        $this->formConfig->addBlock($blockConfig);
        $this->blocks[] = $blockConfig;

        /** test hasBlock */
        $this->assertTrue($this->formConfig->hasBlock('testBlock'));

        /** test getBlock */
        $this->assertEquals($blockConfig, $this->formConfig->getBlock('testBlock'));

        /** test getSubBlock */
        $this->assertEquals($subBlocks[0], $this->formConfig->getSubBlocks('testBlock', 'common'));

        /** test toArray() */
        $this->assertEquals(
            [
                [
                    'title'       => 'Test Block',
                    'class'       => User::class,
                    'subblocks'   => $subBlocksArray,
                    'description' => null
                ]
            ],
            $this->formConfig->toArray()
        );

        /** test getBlocks */
        $this->formConfig->setBlocks($this->blocks);
        $this->assertEquals(
            $this->blocks,
            $this->formConfig->getBlocks()
        );
    }
}
