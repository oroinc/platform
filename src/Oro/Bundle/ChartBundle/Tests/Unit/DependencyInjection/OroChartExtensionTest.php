<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ChartBundle\DependencyInjection\OroChartExtension;

class OroChartExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroChartExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroChartExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }
}
