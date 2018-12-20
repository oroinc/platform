<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ActivityListBundle\DependencyInjection\OroActivityListExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroActivityListExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad()
    {
        $configuration = new ContainerBuilder();
        $loader = new OroActivityListExtension();
        $loader->load([], $configuration);
        $this->assertTrue($configuration instanceof ContainerBuilder);
    }
}
