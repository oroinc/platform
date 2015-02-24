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
            ['title' => '', 'title_parameters' => []],
            $this->resolveOptions(HeadType::NAME, [])
        );
        $this->assertEquals(
            ['title' => 'test', 'title_parameters' => ['{{ foo }}' => 'bar']],
            $this->resolveOptions(HeadType::NAME, ['title' => 'test', 'title_parameters' => ['{{ foo }}' => 'bar']])
        );
    }

    public function testBuildView()
    {
        $view = $this->getBlockBuilder(
            HeadType::NAME,
            ['title' => 'test', 'title_parameters' => ['{{ foo }}' => 'bar']]
        )
            ->add(MetaType::NAME, ['charset' => 'UTF-8'])
            ->add(ScriptType::NAME, [])
            ->add(MetaType::NAME, ['http_equiv' => 'refresh', 'content' => '30'])
            ->getBlockView();

        $this->assertEquals('test', $view->vars['title']);
        $this->assertEquals(['{{ foo }}' => 'bar'], $view->vars['title_parameters']);

        // check that children are in the right order
        $this->assertEquals(
            ['head_id_meta_id1', 'head_id_meta_id3', 'head_id_script_id2'],
            array_keys($view->children)
        );
    }

    public function testGetName()
    {
        $type = $this->getBlockType(HeadType::NAME);

        $this->assertSame(HeadType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(HeadType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
