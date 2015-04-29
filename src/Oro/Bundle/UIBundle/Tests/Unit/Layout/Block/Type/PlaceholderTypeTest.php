<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\UIBundle\Layout\Block\Type\PlaceholderType;

class PlaceholderTypeTest extends BlockTypeTestCase
{
    public function testBuildView()
    {
        $view = $this->getBlockView(
            new PlaceholderType(),
            ['placeholder_name' => 'test', 'placeholder_parameters' => ['{{ foo }}' => 'bar']]
        );

        $this->assertEquals('test', $view->vars['placeholder_name']);
        $this->assertEquals(['{{ foo }}' => 'bar'], $view->vars['placeholder_parameters']);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "placeholder_name" is missing.
     */
    public function testBuildViewThrowsExceptionIfPlaceholderNameIsNotSpecified()
    {
        $this->getBlockView(new PlaceholderType());
    }

    public function testGetName()
    {
        $type = new PlaceholderType();

        $this->assertSame(PlaceholderType::NAME, $type->getName());
    }
}
