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
            [],
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
            ['content' => 'test content'],
            $this->resolveOptions(ScriptType::NAME, ['content' => 'test content'])
        );
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "async" with value "async" is expected to be of type "bool"
     */
    public function testSetDefaultOptionsWithInvalidAsync()
    {
        $this->resolveOptions(ScriptType::NAME, ['src' => 'test.js', 'async' => 'async']);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "defer" with value "defer" is expected to be of type "bool"
     */
    public function testSetDefaultOptionsWithInvalidDefer()
    {
        $this->resolveOptions(ScriptType::NAME, ['src' => 'test.js', 'defer' => 'defer']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "crossorigin" has the value "test", but is expected to be one of "anonymous", "use-credentials"
     */
    // @codingStandardsIgnoreEnd
    public function testSetDefaultOptionsWithInvalidCrossorigin()
    {
        $this->resolveOptions(ScriptType::NAME, ['src' => 'test.js', 'crossorigin' => 'test']);
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
            ['content' => 'test content']
        );

        $this->assertEquals('test content', $view->vars['content']);
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
