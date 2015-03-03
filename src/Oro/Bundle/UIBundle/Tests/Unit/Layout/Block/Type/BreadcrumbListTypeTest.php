<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Extension\PreloadedExtension;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\UIBundle\Layout\Block\Extension\LinkExtension;
use Oro\Bundle\UIBundle\Layout\Block\Type\BreadcrumbListType;

class BreadcrumbListTypeTest extends BlockTypeTestCase
{
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [],
                ['link' => [new LinkExtension()]]
            )
        ];
    }

    public function testFinishView()
    {
        // breadcrumbs
        //   link1
        //   text1
        //   container
        //     link2
        $layoutBuilder = $this->layoutFactory->createLayoutBuilder();
        $layoutBuilder->add('breadcrumbs', null, new BreadcrumbListType(), []);
        $layoutBuilder->add('link1', 'breadcrumbs', 'link', ['path' => 'path1', 'text' => 'link1']);
        $layoutBuilder->add('text1', 'breadcrumbs', 'text', ['text' => 'text1']);
        $layoutBuilder->add('container', 'breadcrumbs', 'container');
        $layoutBuilder->add('link2', 'container', 'link', ['path' => 'path2', 'text' => 'link2']);

        $view = $layoutBuilder->getLayout($this->context)->getView();

        $this->assertTrue($view['link1']->vars['with_page_parameters']);
        $this->assertFalse(isset($view['text1']->vars['with_page_parameters']));
        $this->assertFalse(isset($view['container']->vars['with_page_parameters']));
        $this->assertTrue($view['link2']->vars['with_page_parameters']);
    }

    public function testGetName()
    {
        $type = new BreadcrumbListType();

        $this->assertSame(BreadcrumbListType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = new BreadcrumbListType();

        $this->assertSame('list', $type->getParent());
    }
}
