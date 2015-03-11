<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\HeadType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class HeadTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            [
                'title' => ''
            ],
            $this->resolveOptions(HeadType::NAME, [])
        );
        $this->assertEquals(
            [
                'title' => 'test'
            ],
            $this->resolveOptions(
                HeadType::NAME,
                [
                    'title' => 'test'
                ]
            )
        );
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            HeadType::NAME,
            ['title' => 'test']
        );

        $this->assertEquals('test', $view->vars['title']);
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
