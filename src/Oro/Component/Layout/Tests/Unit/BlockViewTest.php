<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;

class BlockViewTest extends LayoutTestCase
{
    public function testConstruct()
    {
        $rootView = new BlockView();
        $this->assertArrayNotHasKey('value', $rootView->vars);
    }

    public function testAddToVars()
    {
        $parent = new BlockView();
        $firstChild = new BlockView();
        $secondChild = new BlockView();

        $parent->children['first'] = $firstChild;
        $parent->children['second'] = $secondChild;

        $name = 'name';
        $value = 'value';
        $parent->addToVars($name, $value);

        /** @var BlockView $view */
        foreach ([$parent, $firstChild, $secondChild] as $view) {
            $this->assertArrayHasKey($name, $view->vars);
            $this->assertEquals($value, $view->vars[$name]);
        }
    }
}
