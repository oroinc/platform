<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\RequiresType;

class RequiresTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            [],
            $this->resolveOptions(RequiresType::NAME, [])
        );

        $this->assertEquals(
            ['theme' => 'default'],
            $this->resolveOptions(
                RequiresType::NAME,
                ['theme' => 'default']
            )
        );
    }

    public function testBuildViewTheme()
    {
        $view = $this->getBlockView(RequiresType::NAME, ['theme' => 'default']);

        $this->assertEquals('default', $view->vars['theme']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(RequiresType::NAME);

        $this->assertSame(RequiresType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(RequiresType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
