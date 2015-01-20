<?php

namespace Oro\Component\Block;

use Oro\Component\Block\Exception;

class BlockTypeRegistry implements BlockTypeRegistryInterface
{
    /** @var BlockTypeInterface[] */
    private $types = [];

    /** @var BlockTypeFactoryInterface */
    private $blockTypeFactory;

    /**
     * @param BlockTypeFactoryInterface $blockTypeFactory The factory for created block.
     */
    public function __construct(BlockTypeFactoryInterface $blockTypeFactory)
    {
        $this->blockTypeFactory = $blockTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType($name)
    {
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (!isset($this->types[$name])) {
            // Registers the block type.
            $this->types[$name] = $this->blockTypeFactory->createBlockType($name);
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasBlockType($name)
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getBlockType($name);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }
}
