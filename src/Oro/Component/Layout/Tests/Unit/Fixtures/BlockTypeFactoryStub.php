<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

use Oro\Component\Layout\Tests\Unit\BlockTypeFactoryStub as CoreBlockTypeFactoryStub;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\HeaderType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\RootType;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\TestSelfBuildingContainerType;

class BlockTypeFactoryStub extends CoreBlockTypeFactoryStub
{
    public function __construct()
    {
        parent::__construct();
        $this
            ->addBlockType(new RootType())
            ->addBlockType(new HeaderType())
            ->addBlockType(new LogoType())
            ->addBlockType(new TestSelfBuildingContainerType());
    }
}
