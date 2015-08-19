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

    public function testBuildViewCharset()
    {
        $view = $this->getBlockView(MetaType::NAME, ['charset' => 'UTF-8']);

        $this->assertEquals('UTF-8', $view->vars['attr']['charset']);
        $this->assertFalse(isset($view->vars['attr']['http-equiv']));
        $this->assertFalse(isset($view->vars['attr']['content']));
        $this->assertFalse(isset($view->vars['attr']['name']));
    }

    public function testBuildViewDescription()
    {
        $view = $this->getBlockView(MetaType::NAME, ['name' => 'description', 'content' => 'Test']);

        $this->assertEquals('description', $view->vars['attr']['name']);
        $this->assertEquals('Test', $view->vars['attr']['content']);
        $this->assertFalse(isset($view->vars['attr']['charset']));
        $this->assertFalse(isset($view->vars['attr']['http-equiv']));
    }

    public function testBuildViewHttpEquiv()
    {
        $view = $this->getBlockView(MetaType::NAME, ['http_equiv' => 'refresh', 'content' => '30']);

        $this->assertEquals('refresh', $view->vars['attr']['http-equiv']);
        $this->assertEquals('30', $view->vars['attr']['content']);
        $this->assertFalse(isset($view->vars['attr']['charset']));
        $this->assertFalse(isset($view->vars['attr']['name']));
    }

    public function testGetName()
    {
        $type = $this->getBlockType(MetaType::NAME);

        $this->assertSame(MetaType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(MetaType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
