<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit;

use Oro\Bundle\AddressBundle\OroAddressBundle;

class OroAddressBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $bundle = new OroAddressBundle();
        $bundle->build($container);
    }
}
