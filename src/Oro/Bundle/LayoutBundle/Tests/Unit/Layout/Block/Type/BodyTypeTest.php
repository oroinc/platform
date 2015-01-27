<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\BodyType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class BodyTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            [BodyType::OPTIONS_TAG_ATTRIBUTES => []],
            $this->resolveOptions(BodyType::NAME, [BodyType::OPTIONS_TAG_ATTRIBUTES => []])
        );
        $this->assertEquals(
            [BodyType::OPTIONS_TAG_ATTRIBUTES => ['class' => 'desktop', 'id' => 'root_body']],
            $this->resolveOptions(
                BodyType::NAME,
                [BodyType::OPTIONS_TAG_ATTRIBUTES => ['class' => 'desktop', 'id' => 'root_body']]
            )
        );
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            BodyType::NAME,
            [BodyType::OPTIONS_TAG_ATTRIBUTES => ['class' => 'desktop', 'id' => 'root_body']]
        );

        $this->assertEquals('desktop', $view->vars['attr']['class']);
        $this->assertEquals('root_body', $view->vars['attr']['id']);
    }

    public function testGetName()
    {
        $type = $this->factory->createBlockType(BodyType::NAME);

        $this->assertSame(BodyType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->factory->createBlockType(BodyType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
