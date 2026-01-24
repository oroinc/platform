<?php

namespace Oro\Bundle\FormBundle\Config;

use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Manages configuration for a complete form.
 *
 * A form is composed of multiple blocks, each containing sub-blocks and fields.
 * This class manages the collection of blocks, provides access to specific blocks
 * and their sub-blocks, and can serialize the entire form configuration to an array.
 * Blocks are automatically sorted by priority when added or modified.
 */
class FormConfig implements FormConfigInterface
{
    /**
     * @var BlockConfig[]
     */
    protected $blocks = array();

    /**
     * @param BlockConfig $block
     * @return $this
     */
    public function addBlock(BlockConfig $block)
    {
        $this->blocks[$block->getCode()] = $block;

        $this->sortBlocks();

        return $this;
    }

    /**
     * @param $code
     * @return BlockConfig
     */
    public function getBlock($code)
    {
        return $this->blocks[$code];
    }

    /**
     * @param $code
     * @return bool
     */
    public function hasBlock($code)
    {
        return isset($this->blocks[$code]);
    }

    /**
     * @return BlockConfig[]
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @param $blocks
     * @return $this
     */
    public function setBlocks($blocks)
    {
        $this->blocks = $blocks;

        $this->sortBlocks();

        return $this;
    }

    /**
     * @param $blockCode
     * @param $subBlockIndex
     * @return SubBlockConfig
     */
    public function getSubBlocks($blockCode, $subBlockIndex)
    {
        return $this->getBlock($blockCode)->getSubBlock($subBlockIndex);
    }

    /**
     * @return array
     */
    #[\Override]
    public function toArray()
    {
        $result = [];
        foreach ($this->blocks as $block) {
            $result[] = $block->toArray();
        }
        return $result;
    }

    protected function sortBlocks()
    {
        ArrayUtil::sortBy($this->blocks, true);
    }
}
