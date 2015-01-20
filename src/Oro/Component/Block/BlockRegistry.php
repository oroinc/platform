<?php

namespace Oro\Component\Block;

class BlockRegistry implements BlockRegistryInterface
{
    /** @var BlockTypeInterface[] */
    private $types = array();

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
    public function getType($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Expected argument of type string.');
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
    public function hasType($name)
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getType($name);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }
}
