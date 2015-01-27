<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\MetaType;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ScriptType;
use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\HeadType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class HeadTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            ['title' => ''],
            $this->resolveOptions(HeadType::NAME, [])
        );
        $this->assertEquals(
            ['title' => 'test'],
            $this->resolveOptions(HeadType::NAME, ['title' => 'test'])
        );
    }

    public function testBuildView()
    {
        $view = $this->getBlockBuilder(HeadType::NAME, ['title' => 'test'])
            ->add(MetaType::NAME, ['charset' => 'UTF-8'])
            ->add(ScriptType::NAME, [])
            ->add(MetaType::NAME, ['http_equiv' => 'refresh', 'content' => '30'])
            ->getBlockView();

        $this->assertEquals('test', $view->vars['title']);

        // check that children are in the right order
        $this->assertEquals(
            ['head_id_meta_id1', 'head_id_meta_id3', 'head_id_script_id2'],
            array_keys($view->children)
        );
    }

    public function testGetName()
    {
        $type = $this->factory->createBlockType(HeadType::NAME);

        $this->assertSame(HeadType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->factory->createBlockType(HeadType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
