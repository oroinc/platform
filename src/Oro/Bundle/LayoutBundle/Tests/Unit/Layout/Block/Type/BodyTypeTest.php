<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\BodyType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class BodyTypeTest extends BlockTypeTestCase
{
    public function testBuildView()
    {
        $view = $this->getBlockView(
            BodyType::NAME,
            ['attr' => ['id' => 'test_id_attr']]
        );

        $this->assertEquals('test_id_attr', $view->vars['attr']['id']);
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
