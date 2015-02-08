<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit;

use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

use Oro\Bundle\LayoutBundle\Layout\Block\Type;

/**
 * The base test case that helps testing block types
 */
abstract class BlockTypeTestCase extends BaseBlockTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
        $layoutFactoryBuilder
            ->addBlockType(new Type\RootType())
            ->addBlockType(new Type\BodyType())
            ->addBlockType(new Type\HeadType())
            ->addBlockType(new Type\MetaType())
            ->addBlockType(new Type\ScriptType())
            ->addBlockType(new Type\StyleType());
    }
}
