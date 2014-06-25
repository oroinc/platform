<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TrackingBundle\DependencyInjection\OroBundleTrackingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroBundleTrackingExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroBundleTrackingExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroBundleTrackingExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }
}
