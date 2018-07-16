<?php
namespace Oro\Bundle\TagBundle\Tests\Unit;

use Oro\Bundle\TagBundle\OroTagBundle;

class OroTagBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $bundle = new OroTagBundle();
        $bundle->build($container);
    }
}
