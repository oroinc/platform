<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\EntityMergeBundle\DependencyInjection\OroEntityMergeExtension;

class OroEntityMergeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroEntityMergeExtension
     */
    protected $extension;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroEntityMergeExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }
}
