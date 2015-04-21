<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ScriptType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

class ScriptTypeTest extends BlockTypeTestCase
{
    public function testSetDefaultOptions()
    {
        $this->assertEquals(
            ['type' => 'text/javascript'],
            $this->resolveOptions(ScriptType::NAME, [])
        );
        $this->assertEquals(
            ['type' => 'text/javascript', 'src' => 'test.js', 'async' => true],
            $this->resolveOptions(
                ScriptType::NAME,
                ['type' => 'text/javascript', 'src' => 'test.js', 'async' => true]
            )
        );
        $this->assertEquals(
            [
                'type'        => 'text/javascript',
                'content'     => 'test content',
                'crossorigin' => 'anonymous'
            ],
            $this->resolveOptions(
                ScriptType::NAME,
                ['content' => 'test content', 'crossorigin' => 'anonymous']
            )
        );
    }

    public function testBuildViewWithoutAsyncAndDefer()
    {
        $view = $this->getBlockView(
            ScriptType::NAME,
            ['type' => 'text/javascript', 'src' => 'test.js']
        );

        $this->assertEquals('text/javascript', $view->vars['attr']['type']);
        $this->assertEquals('test.js', $view->vars['attr']['src']);
        $this->assertSame('', $view->vars['content']);
    }

    public function testBuildViewWithFalseValueForAsyncAndDefer()
    {
        $view = $this->getBlockView(
            ScriptType::NAME,
            ['src' => 'test.js', 'async' => false, 'defer' => false]
        );

        $this->assertEquals('test.js', $view->vars['attr']['src']);
        $this->assertFalse(isset($view->vars['attr']['async']), 'Unexpected \'async\' attribute');
        $this->assertFalse(isset($view->vars['attr']['defer']), 'Unexpected \'defer\' attribute');
    }

    public function testBuildViewWithTrueValueForAsyncAndDefer()
    {
        $view = $this->getBlockView(
            ScriptType::NAME,
            ['src' => 'test.js', 'async' => true, 'defer' => true]
        );

        $this->assertEquals('test.js', $view->vars['attr']['src']);
        $this->assertEquals('async', $view->vars['attr']['async']);
        $this->assertEquals('defer', $view->vars['attr']['defer']);
    }

    public function testBuildViewWithContent()
    {
        $view = $this->getBlockView(
            ScriptType::NAME,
            ['content' => 'test content', 'crossorigin' => 'anonymous']
        );

        $this->assertEquals('test content', $view->vars['content']);
        $this->assertEquals('anonymous', $view->vars['attr']['crossorigin']);
        $this->assertFalse(isset($view->vars['attr']['src']));
        $this->assertFalse(isset($view->vars['attr']['async']));
        $this->assertFalse(isset($view->vars['attr']['defer']));
    }

    public function testGetName()
    {
        $type = $this->getBlockType(ScriptType::NAME);

        $this->assertSame(ScriptType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ScriptType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
