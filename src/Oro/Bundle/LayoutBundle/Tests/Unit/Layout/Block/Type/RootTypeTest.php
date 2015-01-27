<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\RootType;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\HeadType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class RootTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            ['doctype' => 'html'],
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
            ->add(HeadType::NAME, ['title' => 'test'])
            ->getBlockView();

        $this->assertEquals('html', $view->vars['doctype']);

        $this->assertEquals(
            ['root_id_head_id1'],
            array_keys($view->children)
        );
    }

    public function testGetName()
    {
        $type = $this->factory->createBlockType(RootType::NAME);

        $this->assertSame(RootType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->factory->createBlockType(RootType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
