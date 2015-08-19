<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\StyleType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class StyleTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            ['type' => 'text/css'],
            $this->resolveOptions(StyleType::NAME, [])
        );
        $this->assertEquals(
            ['type' => 'text/css', 'src' => 'test.css', 'scoped' => true],
            $this->resolveOptions(
                StyleType::NAME,
                ['type' => 'text/css', 'src' => 'test.css', 'scoped' => true]
            )
        );
        $this->assertEquals(
            [
                'type'        => 'text/css',
                'content'     => 'test content',
                'media'       => '(max-width: 800px)',
                'crossorigin' => 'anonymous'
            ],
            $this->resolveOptions(
                StyleType::NAME,
                ['content' => 'test content', 'media' => '(max-width: 800px)', 'crossorigin' => 'anonymous']
            )
        );
    }

    public function testBuildViewWithoutScoped()
    {
        $view = $this->getBlockView(
            StyleType::NAME,
            ['type' => 'text/css', 'src' => 'test.css']
        );

        $this->assertEquals('text/css', $view->vars['attr']['type']);
        $this->assertEquals('test.css', $view->vars['attr']['href']);
        $this->assertSame('', $view->vars['content']);
        $this->assertFalse(isset($view->vars['attr']['media']));
        $this->assertFalse(isset($view->vars['attr']['crossorigin']));
    }

    public function testBuildViewWithFalseValueForScoped()
    {
        $view = $this->getBlockView(
            StyleType::NAME,
            ['src' => 'test.css', 'scoped' => false]
        );

        $this->assertEquals('test.css', $view->vars['attr']['href']);
        $this->assertFalse(isset($view->vars['attr']['scoped']), 'Unexpected \'scoped\' attribute');
    }

    public function testBuildViewWithTrueValueForScoped()
    {
        $view = $this->getBlockView(
            StyleType::NAME,
            ['src' => 'test.css', 'scoped' => true]
        );

        $this->assertEquals('test.css', $view->vars['attr']['href']);
        $this->assertEquals('scoped', $view->vars['attr']['scoped']);
    }

    public function testBuildViewWithContent()
    {
        $view = $this->getBlockView(
            StyleType::NAME,
            ['content' => 'test content', 'media' => '(max-width: 800px)', 'crossorigin' => 'anonymous']
        );

        $this->assertEquals('test content', $view->vars['content']);
        $this->assertEquals('(max-width: 800px)', $view->vars['attr']['media']);
        $this->assertEquals('anonymous', $view->vars['attr']['crossorigin']);
        $this->assertFalse(isset($view->vars['attr']['href']));
        $this->assertFalse(isset($view->vars['attr']['scoped']));
    }

    public function testGetName()
    {
        $type = $this->getBlockType(StyleType::NAME);

        $this->assertSame(StyleType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(StyleType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
