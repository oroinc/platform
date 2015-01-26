<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\BlockTypeFactoryInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\Block\Type\BlockType;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\HeaderType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\RootType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\TestSelfBuildingContainerType;

class BlockTypeFactoryStub implements BlockTypeFactoryInterface
{
    protected $types = [];

    public function __construct()
    {
        $this
            ->addBlockType(new BlockType())
            ->addBlockType(new ContainerType())
            ->addBlockType(new RootType())
            ->addBlockType(new HeaderType())
            ->addBlockType(new LogoType())
            ->addBlockType(new TestSelfBuildingContainerType());
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
