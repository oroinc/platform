<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TrackingBundle\DependencyInjection\OroTrackingExtension;

class OroBundleTrackingExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroTrackingExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new OroTrackingExtension();
    }

    public function testLoad()
    {
        $this->container->expects($this->once())
            ->method('prependExtensionConfig')
            ->with('oro_tracking', $this->isType('array'));
        $this->extension->load([], $this->container);
    }
}
