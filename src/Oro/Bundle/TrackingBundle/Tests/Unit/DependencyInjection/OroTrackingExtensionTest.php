<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TrackingBundle\DependencyInjection\OroTrackingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroBundleTrackingExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroTrackingExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroTrackingExtension();
    }

    public function testLoad()
    {
        $this->extension->load([], $this->container);
    }
}
