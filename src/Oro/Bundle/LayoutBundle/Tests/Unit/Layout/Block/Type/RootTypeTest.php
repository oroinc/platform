<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\RootType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class RootTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            ['doctype' => ''],
            $this->resolveOptions(RootType::NAME, [])
        );
        $this->assertEquals(
            ['doctype' => 'html'],
            $this->resolveOptions(RootType::NAME, ['doctype' => 'html'])
        );
    }

    public function testBuildView()
    {
        $view = $this->getBlockBuilder(RootType::NAME, ['doctype' => 'html'])
            ->getBlockView();

        $this->assertEquals('html', $view->vars['doctype']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(RootType::NAME);

        $this->assertSame(RootType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(RootType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
