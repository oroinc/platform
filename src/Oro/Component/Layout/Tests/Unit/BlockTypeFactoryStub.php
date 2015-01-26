<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockTypeFactoryInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\ContainerType;

class BlockTypeFactoryStub implements BlockTypeFactoryInterface
{
    protected $types = [];

    public function __construct()
    {
        $this
            ->addBlockType(new BaseType())
            ->addBlockType(new ContainerType());
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockType($name)
    {
        if (!isset($this->types[$name])) {
            return null;
        }

        return $this->types[$name];
    }

    /**
     * @param BlockTypeInterface $blockType
     *
     * @return self
     */
    public function addBlockType(BlockTypeInterface $blockType)
    {
        $this->types[$blockType->getName()] = $blockType;

        return $this;
    }
}
