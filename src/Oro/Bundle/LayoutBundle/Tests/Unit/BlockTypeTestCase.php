<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit;

use Oro\Component\Layout\Tests\Unit\BaseBlockTypeTestCase;

use Oro\Bundle\LayoutBundle\Layout\Block\Type;

class BlockTypeTestCase extends BaseBlockTypeTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->factory
            ->addBlockType(new Type\RootType())
            ->addBlockType(new Type\BodyType())
            ->addBlockType(new Type\HeadType())
            ->addBlockType(new Type\MetaType())
            ->addBlockType(new Type\ScriptType())
            ->addBlockType(new Type\StyleType());
    }
}
