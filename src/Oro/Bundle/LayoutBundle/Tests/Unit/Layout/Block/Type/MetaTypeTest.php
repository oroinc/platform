<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\MetaType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class MetaTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            [],
            $this->resolveOptions(MetaType::NAME, [])
        );
        $this->assertEquals(
            ['charset' => 'UTF-8'],
            $this->resolveOptions(MetaType::NAME, ['charset' => 'UTF-8'])
        );
        $this->assertEquals(
            ['name' => 'description', 'content' => 'Test'],
            $this->resolveOptions(MetaType::NAME, ['name' => 'description', 'content' => 'Test'])
        );
        $this->assertEquals(
            ['http_equiv' => 'refresh', 'content' => '30'],
            $this->resolveOptions(MetaType::NAME, ['http_equiv' => 'refresh', 'content' => '30'])
        );
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(MetaType::NAME, ['http_equiv' => 'refresh', 'content' => '30']);

        $this->assertEquals('refresh', $view->vars['attr']['http-equiv']);
        $this->assertEquals('30', $view->vars['attr']['content']);
    }

    public function testGetName()
    {
        $type = $this->factory->createBlockType(MetaType::NAME);

        $this->assertSame(MetaType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->factory->createBlockType(MetaType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
