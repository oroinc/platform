<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ActivityListBundle\DependencyInjection\OroActivityListExtension;

class OroActivityListExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $configuration = new ContainerBuilder();
        $loader = new OroActivityListExtension();
        $loader->load([], $configuration);
        $this->assertTrue($configuration instanceof ContainerBuilder);
    }
}
