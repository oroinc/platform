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
            ['type' => 'text/css', 'content' => 'test content'],
            $this->resolveOptions(StyleType::NAME, ['content' => 'test content'])
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "crossorigin" has the value "test", but is expected to be one of "anonymous", "use-credentials"
     */
    // @codingStandardsIgnoreEnd
    public function testSetDefaultOptionsWithInvalidCrossorigin()
    {
        $this->resolveOptions(StyleType::NAME, ['src' => 'test.css', 'crossorigin' => 'test']);
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
            ['content' => 'test content']
        );

        $this->assertEquals('test content', $view->vars['content']);
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
