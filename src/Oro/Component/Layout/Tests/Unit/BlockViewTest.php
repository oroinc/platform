<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;

class BlockViewTest extends \PHPUnit_Framework_TestCase
{
    /** @var BlockView */
    protected $rootView;

    protected function setUp()
    {
        $this->rootView = new BlockView(['base', 'root']);
    }

    public function testIsInstanceOf()
    {
        $this->assertTrue($this->rootView->isInstanceOf('root'));
        $this->assertTrue($this->rootView->isInstanceOf('base'));
        $this->assertFalse($this->rootView->isInstanceOf('another'));
    }

    public function testChildGetAndExists()
    {
        // root
        //   header
        //     title
        //       logo
        $headerView                         = new BlockView(['base', 'header'], $this->rootView);
        $this->rootView->children['header'] = $headerView;
        $titleView                          = new BlockView(['base', 'container'], $headerView);
        $headerView->children['title']      = $titleView;
        $logoView                           = new BlockView(['base', 'logo'], $headerView);
        $titleView->children['logo']        = $logoView;

        $this->assertTrue(
            isset($this->rootView['header']),
            'Failed asserting that "root" contains "header".'
        );
        $this->assertSame(
            $headerView,
            $this->rootView['header'],
            'Failed asserting that "root" returns valid instance of "header".'
        );
        $this->assertTrue(
            isset($this->rootView['title']),
            'Failed asserting that "root" contains "title".'
        );
        $this->assertSame(
            $titleView,
            $this->rootView['title'],
            'Failed asserting that "root" returns valid instance of "title".'
        );
        $this->assertTrue(
            isset($this->rootView['logo']),
            'Failed asserting that "root" contains "logo".'
        );
        $this->assertSame(
            $logoView,
            $this->rootView['logo'],
            'Failed asserting that "root" returns valid instance of "logo".'
        );
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testChildSet()
    {
        $this->rootView['header'] = new BlockView(['base', 'header'], $this->rootView);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testChildUnset()
    {
        $headerView                         = new BlockView(['base', 'header'], $this->rootView);
        $this->rootView->children['header'] = $headerView;

        unset($this->rootView['header']);
    }
}
