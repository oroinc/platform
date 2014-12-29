<?php
namespace Oro\Bundle\NoteBundle\Tests\Unit;

use Oro\Bundle\NoteBundle\OroNoteBundle;

class OroNoteBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $bundle = new OroNoteBundle();
        $bundle->build($container);
    }
}
