<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ExternalResourceType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class ExternalResourceTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            [],
            $this->resolveOptions(ExternalResourceType::NAME, [])
        );
        $this->assertEquals(
            ['type' => 'text/css', 'href' => 'test.css', 'rel' => 'stylesheet'],
            $this->resolveOptions(
                ExternalResourceType::NAME,
                ['type' => 'text/css', 'href' => 'test.css', 'rel' => 'stylesheet']
            )
        );
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            ExternalResourceType::NAME,
            ['type' => 'text/css', 'href' => 'test.css', 'rel' => 'stylesheet']
        );

        $this->assertEquals('text/css', $view->vars['attr']['type']);
        $this->assertEquals('test.css', $view->vars['attr']['href']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(ExternalResourceType::NAME);

        $this->assertSame(ExternalResourceType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ExternalResourceType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
