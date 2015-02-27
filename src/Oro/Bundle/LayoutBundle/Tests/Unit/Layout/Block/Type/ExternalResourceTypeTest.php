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
            ['href' => 'test.css', 'rel' => 'stylesheet'],
            $this->resolveOptions(
                ExternalResourceType::NAME,
                ['href' => 'test.css', 'rel' => 'stylesheet']
            )
        );
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required options "href", "rel" are missing.
     */
    public function testBuildViewThrowsExceptionIfRequiredOptionsNotSpecified()
    {
        $this->getBlockView(new ExternalResourceType());
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            ExternalResourceType::NAME,
            ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'test.css', ]
        );

        $this->assertEquals('stylesheet', $view->vars['attr']['rel']);
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
