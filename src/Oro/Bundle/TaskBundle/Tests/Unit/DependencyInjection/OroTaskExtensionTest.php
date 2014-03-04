<?php

namespace Oro\Bundle\TaskBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TaskBundle\DependencyInjection\OroTaskExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroTaskExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroTaskExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroTaskExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }
}
