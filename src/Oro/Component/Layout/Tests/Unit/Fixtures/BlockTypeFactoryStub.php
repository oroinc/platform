<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\Block\BaseType;
use Oro\Component\Layout\BlockTypeFactoryInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\HeaderType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\LogoType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\RootType;

class BlockTypeFactoryStub implements BlockTypeFactoryInterface
{
    protected $types = [];

    public function __construct()
    {
        $this
            ->addBlockType(new BaseType())
            ->addBlockType(new RootType())
            ->addBlockType(new HeaderType())
            ->addBlockType(new LogoType());
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockType($name)
    {
        if (!isset($this->types[$name])) {
            throw new \RuntimeException(sprintf('Unknown block type: %s.', $name));
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
