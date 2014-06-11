<?php
namespace Oro\Bundle\NoteBundle\Tests\Unit;

use Oro\Bundle\ActivityBundle\OroActivityBundle;

class OroActivityBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $bundle = new OroActivityBundle();
        $bundle->build($container);
    }
}
