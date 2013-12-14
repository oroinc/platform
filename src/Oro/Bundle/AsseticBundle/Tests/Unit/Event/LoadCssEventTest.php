<?php
namespace Oro\Bundle\AsseticBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AsseticBundle\Event\LoadCssEvent;

class LoadCssEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $assetsConfiguration;

    /**
     * @var LoadCssEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->assetsConfiguration = $this->getMockBuilder('Oro\Bundle\AsseticBundle\AssetsConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = new LoadCssEvent($this->assetsConfiguration);
    }

    public function testAddCss()
    {
        $group = 'test';
        $files = array('styles.css');

        $this->assetsConfiguration->expects($this->once())
            ->method('addCss')
            ->with($group, $files);

        $this->event->addCss($group, $files);
    }
}
