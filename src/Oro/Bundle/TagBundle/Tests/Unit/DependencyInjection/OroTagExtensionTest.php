<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TagBundle\DependencyInjection\OroTagExtension;

class OroTagExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad()
    {
        $extension = new OroTagExtension();
        $configs = array();
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $extension->load($configs, $container);
    }
}
