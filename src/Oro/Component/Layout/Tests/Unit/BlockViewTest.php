<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;

class BlockViewTest extends \PHPUnit_Framework_TestCase
{
    /** @var BlockView */
    protected $rootView;

    protected function setUp()
    {
        $this->rootView = new BlockView();
    }

    public function testChildGetAndExists()
    {
        // root
        //   header
        //     title
        //       logo
        $headerView                         = new BlockView($this->rootView);
        $this->rootView->children['header'] = $headerView;
        $titleView                          = new BlockView($headerView);
        $headerView->children['title']      = $titleView;
        $logoView                           = new BlockView($headerView);
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

    public function testExistsForUnknownChild()
    {
        // root
        //   header
        //     title
        $headerView                         = new BlockView($this->rootView);
        $this->rootView->children['header'] = $headerView;
        $titleView                          = new BlockView($headerView);
        $headerView->children['title']      = $titleView;

        $this->assertFalse(isset($this->rootView['unknown']));
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: unknown.
     */
    public function testGetForUnknownChild()
    {
        // root
        //   header
        //     title
        $headerView                         = new BlockView($this->rootView);
        $this->rootView->children['header'] = $headerView;
        $titleView                          = new BlockView($headerView);
        $headerView->children['title']      = $titleView;

        $this->rootView['unknown'];
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testChildSet()
    {
        $this->rootView['header'] = new BlockView($this->rootView);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testChildUnset()
    {
        $headerView                         = new BlockView($this->rootView);
        $this->rootView->children['header'] = $headerView;

        unset($this->rootView['header']);
    }
}
